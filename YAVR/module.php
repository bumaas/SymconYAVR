<?php

/** @noinspection AutoloadingIssuesInspection */
class YAVR extends IPSModule
{
    private const IS_ERROR_AVR_NOT_REACHABLE = 201;
    private const IS_ERROR_OTHER             = 202;

    private const TIMER_UPDATE = 'Update';


    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyString('Zone', 'Main_Zone');
        $this->RegisterPropertyInteger('UpdateInterval', 5);
        $this->RegisterPropertyString('InputsMapping', '');
        $this->RegisterPropertyString('ScenesMapping', '');

        if (!IPS_VariableProfileExists('Volume.YAVR')) {
            IPS_CreateVariableProfile('Volume.YAVR', 2);
        }
        IPS_SetVariableProfileDigits('Volume.YAVR', 1);
        IPS_SetVariableProfileIcon('Volume.YAVR', 'Intensity');
        IPS_SetVariableProfileText('Volume.YAVR', '', ' dB');
        IPS_SetVariableProfileValues('Volume.YAVR', -80, 16, 0.5);

        $this->RegisterTimer(self::TIMER_UPDATE, 0, 'YAVR_RequestStatus($_IPS[\'TARGET\'], 0);');

        if ($oldInterval = @$this->GetValue('INTERVAL')) {
            IPS_DeleteEvent($oldInterval);
        }
    }

    public function Destroy()
    {
        parent::Destroy();
        if (IPS_VariableProfileExists("YAVR.Scenes{$this->InstanceID}")) {
            IPS_DeleteVariableProfile("YAVR.Scenes{$this->InstanceID}");
        }
        if (IPS_VariableProfileExists("YAVR.Input{$this->InstanceID}")) {
            IPS_DeleteVariableProfile("YAVR.Inputs{$this->InstanceID}");
        }
    }

    public function GetInputId(string $value): ?int
    {
        $inputs = json_decode($this->ReadPropertyString('InputsMapping'), true, 512, JSON_THROW_ON_ERROR);

        foreach ($inputs as $id => $data) {
            if ($value === $data['id']){
                return $id;
            }
        }

        trigger_error("Invalid input key '$value'", E_USER_ERROR);
        return null;
    }

    public function GetInputKey(int $value): ?string
    {
        $inputs = json_decode($this->ReadPropertyString('InputsMapping'), true, 512, JSON_THROW_ON_ERROR);
        if (isset($inputs[$value])){
            return $inputs[$value]['id'];
        }

        trigger_error("Invalid input id '$value'", E_USER_ERROR);
        return null;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        if ($this->ReadPropertyString('ScenesMapping') === '') {
            $this->UpdateScenes();
        }
        if ($this->ReadPropertyString('InputsMapping') === '') {
            $this->UpdateInputs();
        }

        $this->RegisterVariableBoolean('STATE', 'Zustand', '~Switch', 1);
        $this->EnableAction('STATE');

        $muteId = $this->RegisterVariableBoolean('MUTE', 'Mute', '~Switch', 3);
        IPS_SetIcon($muteId, 'Speaker');
        $this->EnableAction('MUTE');

        $this->RegisterVariableFloat('VOLUME', 'Volume', 'Volume.YAVR', 2);
        $this->EnableAction('VOLUME');

        $this->RequestStatus();
        $this->SetTimerInterval(self::TIMER_UPDATE, $this->ReadPropertyInteger('UpdateInterval') * 1000);

        if ($this->ReadPropertyString('Zone') !== ''){
            $this->SetSummary(sprintf('%s:%s', $this->ReadPropertyString('Host'), $this->ReadPropertyString('Zone')));
        } else {
            $this->SetSummary($this->ReadPropertyString('Host'));
        }
    }

    protected function UpdateScenesProfile():void
    {
        $scenes = json_decode($this->ReadPropertyString('ScenesMapping'), true, 512, JSON_THROW_ON_ERROR);
        if (!IPS_VariableProfileExists("YAVR.Scenes{$this->InstanceID}")) {
            IPS_CreateVariableProfile("YAVR.Scenes{$this->InstanceID}", 1);
        }
        IPS_SetVariableProfileAssociation("YAVR.Scenes{$this->InstanceID}", 0, 'Auswahl', '', -1);
        foreach ($scenes as $key => $name) {
            IPS_SetVariableProfileAssociation("YAVR.Scenes{$this->InstanceID}", $key, $name, '', -1);
        }
    }

    protected function UpdateInputsProfile():void
    {
        $inputs = json_decode($this->ReadPropertyString('InputsMapping'), true, 512, JSON_THROW_ON_ERROR);
        if (!IPS_VariableProfileExists("YAVR.Inputs{$this->InstanceID}")) {
            IPS_CreateVariableProfile("YAVR.Inputs{$this->InstanceID}", 1);
        }
        IPS_SetVariableProfileAssociation("YAVR.Inputs{$this->InstanceID}", 0, 'Auswahl', '', -1);
        foreach ($inputs as $key => $data) {
            IPS_SetVariableProfileAssociation("YAVR.Inputs{$this->InstanceID}", $key, $data['title'], '', -1);
        }
    }

    public function RequestAction($ident, $value)
    {
        switch ($ident) {
      case 'STATE':
         $this->SetState($value);
         break;
      case 'SCENE':
         if ($value > 0) {
             $value = "Scene $value";
             $this->SetScene($value);
         }
         break;
      case 'INPUT':
         if ($value > 0) {
             $value = $this->GetInputKey($value);
             $this->SetInput($value);
         }
         break;
      case 'MUTE':
         $this->SetMute($value);
         break;
      case 'VOLUME':
         $this->SetVolume($value);
         break;
    }
    }

    public function RequestStatus()
    {
        $data = $this->Request('<Basic_Status>GetParam</Basic_Status>', 'GET');
        if ($data === false) {
            return false;
        }
        /*
<YAMAHA_AV rsp="GET" RC="0">
<Main_Zone>
	<Basic_Status>
		<Power_Control>
			<Power>Standby</Power>
			<Sleep>Off</Sleep>
		</Power_Control>
		<Volume>
			<Lvl>
				<Val>-385</Val>
				<Exp>1</Exp>
				<Unit>dB</Unit>
			</Lvl>
			<Mute>Off</Mute>
			<Subwoofer_Trim>
				<Val>0</Val>
				<Exp>1</Exp>
				<Unit>dB</Unit>
			</Subwoofer_Trim>
			<Scale>dB</Scale>
		</Volume>
		<Input>
	        <Input_Sel>AV1</Input_Sel>
        ....
         */
        $data = $data->Basic_Status;

        $this->SetValue('STATE', $data->Power_Control->Power == 'On');

        $inputId = $this->GetInputId((string) $data->Input->Input_Sel);
        if ($inputId !== null) {
            $this->SetValue('INPUT', $inputId);
        }

        $this->SetValue('VOLUME', round($data->Volume->Lvl->Val / 10, 1));

        $this->SetValue('MUTE', $data->Volume->Mute == 'On');

        return $data;
    }

    public function Request(string $partial, string $cmd = 'GET')
    {
        $host = $this->ReadPropertyString('Host');
        $zone = $this->ReadPropertyString('Zone');
        $cmd = strtoupper($cmd);
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= "<YAMAHA_AV cmd=\"{$cmd}\">";
        $xml .= "<{$zone}>{$partial}</{$zone}>";
        $xml .= '</YAMAHA_AV>';
        $client = curl_init();
        $url = "http://$host:80/YamahaRemoteControl/ctrl";
        curl_setopt($client, CURLOPT_URL, "http://$host:80/YamahaRemoteControl/ctrl");
        curl_setopt($client, CURLOPT_USERAGENT, 'SymconYAVR');
        curl_setopt($client, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($client, CURLOPT_TIMEOUT, 2);
        curl_setopt($client, CURLOPT_POST, true);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($client, CURLOPT_POSTFIELDS, $xml);
        $this->SendDebug(__FUNCTION__, sprintf('url: %s, postfields: %s', $url, $xml), 0);
        $result = curl_exec($client);
        $responseCode = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
        curl_close($client);

        $this->SendDebug(__FUNCTION__, sprintf('ResponseCode: %s, result: %s', $responseCode, $result), 0);

        if ($responseCode === 0) {
            $this->SetStatus(self::IS_ERROR_AVR_NOT_REACHABLE);
            return false;
        }

        if ($responseCode !== 200) {
            $this->SetStatus(self::IS_ERROR_OTHER);
            return false;
        }

        $this->SetStatus(IS_ACTIVE);
        if ($cmd === 'PUT') {
            return true;
        }

        return simplexml_load_string($result)->$zone;
    }

    public function RequestExtendedControl(string $partialPath, string $method, string $zone, string $jsonData): string
    {
        if ($jsonData !== '') {
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $data = [];
        }

        $baseURL = sprintf('http://%s/YamahaExtendedControl/v1', $this->ReadPropertyString('Host'));

        if ($zone !== ''){
            $url = implode('/', [$baseURL, $zone, $partialPath]);
        } else {
            $url = implode('/', [$baseURL, $partialPath]);
        }

        if (($method === 'GET') && count($data)) {
            $url .= '?' . http_build_query($data);
        }

        $client = curl_init();
        curl_setopt($client, CURLOPT_URL, $url);
        curl_setopt($client, CURLOPT_USERAGENT, 'SymconYAVR');
        curl_setopt($client, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($client, CURLOPT_TIMEOUT, 2);
        curl_setopt($client, CURLOPT_POST, $method === 'POST');
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);

        if (($method = 'PUT') && count($data)) {
            curl_setopt($client, CURLOPT_POSTFIELDS, $data);
            $this->SendDebug(__FUNCTION__, sprintf('url: %s, data: %s', $url, json_encode($data, JSON_THROW_ON_ERROR, 512)), 0);
        } else {
            $this->SendDebug(__FUNCTION__, sprintf('url: %s', $url), 0);
        }

        $result       = curl_exec($client);
        $responseCode = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
        curl_close($client);

        $this->SendDebug(__FUNCTION__, sprintf('ResponseCode: %s, result: %s', $responseCode, $result), 0);

        return $result;
    }

    private function SetState(bool $state)
    {
        $this->SetValue('STATE', $state);
        $power = $state ? 'On' : 'Standby';
        return $this->Request("<Power_Control><Power>{$power}</Power></Power_Control>", 'PUT');
    }

    private function SetMute(bool $state)
    {
        $this->SetValue('MUTE', $state);
        $mute = $state ? 'On' : 'Off';
        return $this->Request("<Volume><Mute>{$mute}</Mute></Volume>", 'PUT');
    }

    public function SetScene(string $scene)
    {
        return $this->Request("<Scene><Scene_Sel>{$scene}</Scene_Sel></Scene>", 'PUT');
    }

    public function SetInput(string $input)
    {
        $this->SetValue('INPUT', $this->GetInputId($input));
        return $this->Request("<Input><Input_Sel>{$input}</Input_Sel></Input>", 'PUT');
    }

    private function SetVolume($volume)
    {
        if ($volume < -80) {
            $volume = -80;
        }
        if ($volume > 16) {
            $volume = -20;
        } // dont use maximum 16 - if wrong parameter it will not be to loud
        $this->SetValue('VOLUME', $volume);
        $volume *= 10;
        return $this->Request("<Volume><Lvl><Val>{$volume}</Val><Exp>1</Exp><Unit>dB</Unit></Lvl></Volume>", 'PUT');
    }

    public function UpdateScenes()
    {
        $result = array();
        $data = $this->Request('<Scene><Scene_Sel_Item>GetParam</Scene_Sel_Item></Scene>', 'GET');
        if ($data === false) {
            return false;
        }
/*
<Scene>
	<Scene_Sel_Item>
	<Item_1>
		<Param>Scene 1</Param>
		<RW>W</RW>
		<Title>Netflix</Title>
		<Icon><On>1</On><Off>0</Off></Icon>
		<Src_Name></Src_Name>
		<Src_Number>1</Src_Number>
	</Item_1>
    ...
*/

        foreach ((array) $data->Scene->Scene_Sel_Item as $id => $item) {
            $item = (array) $item;
            if ($item['RW'] === 'W') {
                $result[str_replace('Scene ', '', $item['Param'])] = htmlspecialchars_decode((string) $item['Title']);
            }
        }
        IPS_SetProperty($this->InstanceID, 'ScenesMapping', json_encode($result, JSON_THROW_ON_ERROR, 512));
        IPS_ApplyChanges($this->InstanceID);

        $this->UpdateScenesProfile();

        $sceneId = $this->RegisterVariableInteger('SCENE', 'Szene', "YAVR.Scenes{$this->InstanceID}", 8);
        $this->EnableAction('SCENE');
        IPS_SetIcon($sceneId, 'HollowArrowRight');

        $resultText = "ID\tName\n";
        foreach ($result as $id => $data) {
            $resultText .= "$id\t{$data}\n";
        }
        return $resultText;
    }


    public function UpdateInputs()
    {
        $data = $this->Request('<Input><Input_Sel_Item>GetParam</Input_Sel_Item></Input>', 'GET');
        if ($data === false) {
            return false;
        }
/*
<Input>
	<Input_Sel_Item>
		<Item_1>
			<Param>Napster</Param>
			<RW>R</RW>
			<Title>Napster</Title>
			<Icon><On>130</On><Off>0</Off></Icon>
			<Src_Name>Napster</Src_Name>
			<Src_Number>1</Src_Number>
		</Item_1>
		<Item_2>
			<Param>Spotify</Param>
			<RW>R</RW>
			<Title>Spotify</Title>
			<Icon><On>133</On><Off>0</Off></Icon>
			<Src_Name>Spotify</Src_Name>
			<Src_Number>1</Src_Number>
		</Item_2>
        ...
		<Item_6>
			<Param>Amazon Music</Param>
			<RW>RW</RW>
			<Title>Amazon Music</Title>
			<Icon><On>139</On><Off>0</Off></Icon>
			<Src_Name>Amazon_Music</Src_Name>
			<Src_Number>1</Src_Number>
		</Item_6>
        ...
 */

        $result = [];
        $counter = 0;

        foreach ((array) $data->Input->Input_Sel_Item as $id => $item) {
            $counter++;
            $item = (array) $item;
            if ($item['RW'] === 'RW') {
                $result[$counter] = ['id' => (string) $item['Param'],
                                     'title' => (string)$item['Title']];
            }
        }

        IPS_SetProperty($this->InstanceID, 'InputsMapping', json_encode($result, JSON_THROW_ON_ERROR, 512));
        IPS_ApplyChanges($this->InstanceID);

        $this->UpdateInputsProfile();

        $inputId = $this->RegisterVariableInteger('INPUT', 'Eingang', "YAVR.Inputs{$this->InstanceID}", 9);
        $this->EnableAction('INPUT');
        IPS_SetIcon($inputId, 'ArrowRight');

        $resultText = "Symcon ID\tAVR Param\t\tTitel\n";
        foreach ($result as $id => $data) {
            $resultText .= "$id\t\t{$data['id']}\t\t{$data['title']}\n";
        }
        return $resultText;
    }
}
