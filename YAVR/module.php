<?php

/** @noinspection AutoloadingIssuesInspection */
class YAVR extends IPSModule
{
    private const IS_ERROR_AVR_NOT_REACHABLE = 201;
    private const IS_ERROR_OTHER             = 202;

    //property names
    private const PROP_HOST           = 'Host';
    private const PROP_ZONE           = 'Zone';
    private const PROP_UPDATEINTERVAL = 'UpdateInterval';

    //attribute names
    private const ATTR_INPUTSMAPPING = 'InputsMapping';
    private const ATTR_SCENESMAPPING = 'ScenesMapping';

    //status variable names
    private const VAR_SCENE           = 'Scene';
    private const VAR_INPUT           = 'Input';
    private const VAR_STATE           = 'State';
    private const VAR_MUTE            = 'Mute';
    private const VAR_VOLUME          = 'Volume';
    private const VAR_SOUNDPROGRAM    = 'SoundProgram';
    private const VAR_PARTYMODE       = 'PartyMode';
    private const VAR_PUREDIRECT      = 'PureDirect';
    private const VAR_ENHANCER        = 'Enhancer';
    private const VAR_TONECONTROL     = 'ToneControl';
    private const VAR_BASS            = 'Bass';
    private const VAR_TREBLE          = 'Treble';
    private const VAR_DIALOGUELEVEL   = 'DialogueLevel';
    private const VAR_DIALOGUELIFT    = 'DialogueLift';
    private const VAR_SUBWOOFERVOLUME = 'SubwooferVolume';
    private const VAR_SURROUNDAI      = 'SurroundAI';


    //timer names
    private const TIMER_UPDATE = 'Update';

    private const SOUNDPROGRAMS = [
        1  => ['name' => 'munich_a', 'caption' => 'Munich A'],
        2  => ['name' => 'munich_b', 'caption' => 'Munich B'],
        3  => ['name' => 'munich', 'caption' => 'Hall in Munich'],
        4  => ['name' => 'frankfurt', 'caption' => 'Hall in Frankfurt'],
        5  => ['name' => 'stuttgart', 'caption' => 'Hall in Stuttgart'],
        6  => ['name' => 'vienna', 'caption' => 'Hall in Vienna'],
        7  => ['name' => 'amsterdam', 'caption' => 'Hall in Amsterdam'],
        8  => ['name' => 'usa_a', 'caption' => 'USA A'],
        9  => ['name' => 'usa_b', 'caption' => 'USA B'],
        10 => ['name' => 'tokyo', 'caption' => 'Tokyo'],
        11 => ['name' => 'freiburg', 'caption' => 'Church in Freiburg'],
        12 => ['name' => 'royaumont', 'caption' => 'Church in Royaumont'],
        13 => ['name' => 'chamber', 'caption' => 'Chamber'],
        14 => ['name' => 'concert', 'caption' => 'Concert'],
        15 => ['name' => 'village_gate', 'caption' => 'Village Gate'],
        16 => ['name' => 'village_vanguard', 'caption' => 'Village Vanguard'],
        17 => ['name' => 'warehouse_loft', 'caption' => 'Warehouse Loft'],
        18 => ['name' => 'cellar_club', 'caption' => 'Cellar Club'],
        19 => ['name' => 'jazz_club', 'caption' => 'Jazz Club'],
        20 => ['name' => 'roxy_theatre', 'caption' => 'The Roxy Theatre'],
        21 => ['name' => 'bottom_line', 'caption' => 'The Bottom Line'],
        22 => ['name' => 'arena', 'caption' => 'Arena'],
        23 => ['name' => 'sports', 'caption' => 'Sports'],
        24 => ['name' => 'action_game', 'caption' => 'Action Game'],
        25 => ['name' => 'roleplaying_game', 'caption' => 'Roleplaying Game'],
        26 => ['name' => 'game', 'caption' => 'Game'],
        27 => ['name' => 'music_video', 'caption' => 'Music Video'],
        28 => ['name' => 'music', 'caption' => 'Music'],
        29 => ['name' => 'recital_opera', 'caption' => 'Recital/Opera'],
        30 => ['name' => 'pavilion', 'caption' => 'Pavilion'],
        31 => ['name' => 'disco', 'caption' => 'Disco'],
        32 => ['name' => 'standard', 'caption' => 'Standard'],
        33 => ['name' => 'spectacle', 'caption' => 'Spectacle'],
        34 => ['name' => 'sci-fi', 'caption' => 'Sci-Fi'],
        35 => ['name' => 'adventure', 'caption' => 'Adventure'],
        36 => ['name' => 'drama', 'caption' => 'Drama'],
        37 => ['name' => 'talk_show', 'caption' => 'Talk Show'],
        38 => ['name' => 'tv_program', 'caption' => 'TV Program'],
        39 => ['name' => 'mono_movie', 'caption' => 'Mono Movie'],
        40 => ['name' => 'movie', 'caption' => 'Movie'],
        41 => ['name' => 'enhanced', 'caption' => 'Enhanced'],
        42 => ['name' => '2ch_stereo', 'caption' => '2ch Stereo'],
        43 => ['name' => '5ch_stereo', 'caption' => '5ch Stereo'],
        44 => ['name' => '7ch_stereo', 'caption' => '7ch Stereo'],
        45 => ['name' => '9ch_stereo', 'caption' => '9ch Stereo'],
        46 => ['name' => '11ch_stereo', 'caption' => '11ch Stereo'],
        47 => ['name' => 'stereo', 'caption' => 'Stereo'],
        48 => ['name' => 'surr_decoder', 'caption' => 'Surround Decoder'],
        49 => ['name' => 'my_surround', 'caption' => 'My Surround'],
        50 => ['name' => 'target', 'caption' => 'Target'],
        51 => ['name' => 'bass_booster', 'caption' => 'Bass Booster'],
        52 => ['name' => 'straight', 'caption' => 'Straight'],
        53 => ['name' => 'off', 'caption' => 'DSP Off'],
    ];


    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString(self::PROP_HOST, '');
        $this->RegisterPropertyString(self::PROP_ZONE, 'Main_Zone');
        $this->RegisterPropertyInteger(self::PROP_UPDATEINTERVAL, 5);

        $this->RegisterAttributeString(self::ATTR_INPUTSMAPPING, json_encode([], JSON_THROW_ON_ERROR, 512));
        $this->RegisterAttributeString(self::ATTR_SCENESMAPPING, json_encode([], JSON_THROW_ON_ERROR, 512));

        //Volume Profile
        if (!IPS_VariableProfileExists('YAVR.Volume')) {
            IPS_CreateVariableProfile('YAVR.Volume', VARIABLETYPE_FLOAT);
        }
        IPS_SetVariableProfileDigits('YAVR.Volume', 1);
        IPS_SetVariableProfileIcon('YAVR.Volume', 'Intensity');
        IPS_SetVariableProfileText('YAVR.Volume', '', ' dB');
        IPS_SetVariableProfileValues('YAVR.Volume', -80, 16, 0.5);

        //Bass Profile
        if (!IPS_VariableProfileExists('YAVR.Bass')) {
            IPS_CreateVariableProfile('YAVR.Bass', VARIABLETYPE_FLOAT);
        }
        IPS_SetVariableProfileDigits('YAVR.Bass', 1);
        IPS_SetVariableProfileIcon('YAVR.Bass', 'Intensity');
        IPS_SetVariableProfileText('YAVR.Bass', '', ' dB');
        IPS_SetVariableProfileText('YAVR.Bass', '', '');
        IPS_SetVariableProfileValues('YAVR.Bass', -6, 6, 0.5);

        //Treble Profile
        if (!IPS_VariableProfileExists('YAVR.Treble')) {
            IPS_CreateVariableProfile('YAVR.Treble', VARIABLETYPE_FLOAT);
        }
        IPS_SetVariableProfileDigits('YAVR.Treble', 1);
        IPS_SetVariableProfileIcon('YAVR.Treble', 'Intensity');
        IPS_SetVariableProfileText('YAVR.Treble', '', ' dB');
        IPS_SetVariableProfileText('YAVR.Treble', '', '');
        IPS_SetVariableProfileValues('YAVR.Treble', -6, 6, 0.5);

        //DialogueLevel Profile
        if (!IPS_VariableProfileExists('YAVR.DialogueLevel')) {
            IPS_CreateVariableProfile('YAVR.DialogueLevel', VARIABLETYPE_INTEGER);
        }
        IPS_SetVariableProfileDigits('YAVR.DialogueLevel', 1);
        IPS_SetVariableProfileIcon('YAVR.DialogueLevel', 'Intensity');
        IPS_SetVariableProfileText('YAVR.DialogueLevel', '', '');
        IPS_SetVariableProfileValues('YAVR.DialogueLevel', 0, 3, 1);

        //DialogueLift Profile
        if (!IPS_VariableProfileExists('YAVR.DialogueLift')) {
            IPS_CreateVariableProfile('YAVR.DialogueLift', VARIABLETYPE_INTEGER);
        }
        IPS_SetVariableProfileDigits('YAVR.DialogueLift', 1);
        IPS_SetVariableProfileIcon('YAVR.DialogueLift', 'Intensity');
        IPS_SetVariableProfileText('YAVR.DialogueLift', '', '');
        IPS_SetVariableProfileValues('YAVR.DialogueLift', 0, 5, 1);

        //SubwooferVolume Profile
        if (!IPS_VariableProfileExists('YAVR.SubwooferVolume')) {
            IPS_CreateVariableProfile('YAVR.SubwooferVolume', VARIABLETYPE_FLOAT);
        }
        IPS_SetVariableProfileDigits('YAVR.SubwooferVolume', 1);
        IPS_SetVariableProfileIcon('YAVR.SubwooferVolume', 'Intensity');
        IPS_SetVariableProfileText('YAVR.SubwooferVolume', '', ' dB');
        IPS_SetVariableProfileText('YAVR.SubwooferVolume', '', '');
        IPS_SetVariableProfileValues('YAVR.SubwooferVolume', -6, 6, 0.5);

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

    private function GetInputId(string $value): ?int
    {
        $inputs = json_decode($this->ReadAttributeString(self::ATTR_INPUTSMAPPING), true, 512, JSON_THROW_ON_ERROR);

        if (count($inputs) === 0) {
            return null;
        }

        foreach ($inputs as $id => $data) {
            if ($value === $data['id']) {
                return $id;
            }
        }

        trigger_error("Invalid input key '$value'", E_USER_ERROR);
        return null;
    }

    private function GetInputKey(int $value): ?string
    {
        $inputs = json_decode($this->ReadAttributeString(self::ATTR_INPUTSMAPPING), true, 512, JSON_THROW_ON_ERROR);

        if (count($inputs) === 0) {
            return null;
        }

        if (isset($inputs[$value])) {
            return $inputs[$value]['id'];
        }

        trigger_error("Invalid input id '$value'", E_USER_ERROR);
        return null;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->RegisterVariableBoolean(self::VAR_STATE, 'Zustand', '~Switch', 0);
        $this->EnableAction(self::VAR_STATE);

        $this->RegisterVariableBoolean(self::VAR_MUTE, 'Mute', '~Switch', 0);
        $this->EnableAction(self::VAR_MUTE);

        $this->RegisterVariableBoolean(self::VAR_PARTYMODE, 'Party Mode', '~Switch', 0);
        $this->EnableAction(self::VAR_PARTYMODE);

        $this->RegisterVariableBoolean(self::VAR_PUREDIRECT, 'Pure Direct', '~Switch', 0);
        $this->EnableAction(self::VAR_PUREDIRECT);

        $this->RegisterVariableBoolean(self::VAR_ENHANCER, 'Enhancer', '~Switch', 0);
        $this->EnableAction(self::VAR_ENHANCER);

        $this->RegisterVariableBoolean(self::VAR_TONECONTROL, 'ToneControl', '~Switch', 0);
        $this->EnableAction(self::VAR_TONECONTROL);

        $this->RegisterVariableFloat(self::VAR_VOLUME, 'Volume', 'YAVR.Volume', 0);
        $this->EnableAction(self::VAR_VOLUME);

        $this->RegisterVariableFloat(self::VAR_BASS, 'Bass', 'YAVR.Bass', 0);
        $this->EnableAction(self::VAR_BASS);

        $this->RegisterVariableFloat(self::VAR_TREBLE, 'Treble', 'YAVR.Treble', 0);
        $this->EnableAction(self::VAR_TREBLE);

        $this->RegisterVariableInteger(self::VAR_DIALOGUELEVEL, 'Dialogue Level', 'YAVR.DialogueLevel', 0);
        $this->EnableAction(self::VAR_DIALOGUELEVEL);

        $this->RegisterVariableInteger(self::VAR_DIALOGUELIFT, 'Dialogue Lift', 'YAVR.DialogueLift', 0);
        $this->EnableAction(self::VAR_DIALOGUELIFT);

        $this->RegisterVariableFloat(self::VAR_SUBWOOFERVOLUME, 'Subwoofer Volume', 'YAVR.SubwooferVolume', 0);
        $this->EnableAction(self::VAR_SUBWOOFERVOLUME);

        $this->RegisterVariableBoolean(self::VAR_SURROUNDAI, 'Surround AI', '~Switch', 0);
        $this->EnableAction(self::VAR_SURROUNDAI);

        $this->RequestStatus();
        $this->SetTimerInterval(self::TIMER_UPDATE, $this->ReadPropertyInteger('UpdateInterval') * 1000);

        if ($this->ReadPropertyString('Zone') !== '') {
            $this->SetSummary(sprintf('%s:%s', $this->ReadPropertyString(self::PROP_HOST), $this->ReadPropertyString('Zone')));
        } else {
            $this->SetSummary($this->ReadPropertyString(self::PROP_HOST));
        }

        if ($this->GetStatus() === IS_ACTIVE) {
            $this->UpdateScenes();
            $this->UpdateInputs();
            $this->UpdateSoundPrograms();
        }

        $this->UpdateProfiles();
    }

    private function UpdateProfiles(): void
    {
        $Features = $this->RequestExtendedControlList('getFeatures', 'GET', '', '');

        if ($Features['response_code'] !== 0) {
            return;
        }

        //dimmer
        //foreach ($Features['system']['zone'][$this->getZoneIndex()])
    }

    private function UpdateScenesProfile(): void
    {
        $scenes = json_decode($this->ReadAttributeString(self::ATTR_SCENESMAPPING), true, 512, JSON_THROW_ON_ERROR);
        if (!IPS_VariableProfileExists("YAVR.Scenes{$this->InstanceID}")) {
            IPS_CreateVariableProfile("YAVR.Scenes{$this->InstanceID}", 1);
        }

        foreach ($scenes as $key => $name) {
            IPS_SetVariableProfileAssociation("YAVR.Scenes{$this->InstanceID}", $key, $name, '', -1);
        }
    }

    private function UpdateSoundProgramsProfile(array $soundPrograms): void
    {
        if (!IPS_VariableProfileExists("YAVR.SoundPrograms{$this->InstanceID}")) {
            IPS_CreateVariableProfile("YAVR.SoundPrograms{$this->InstanceID}", VARIABLETYPE_INTEGER);
        }
        foreach ($soundPrograms as $soundProgram) {
            foreach (self::SOUNDPROGRAMS as $key => $item) {
                if ($soundProgram === $item['name']) {
                    IPS_SetVariableProfileAssociation("YAVR.SoundPrograms{$this->InstanceID}", $key, $item['caption'], '', -1);
                }
            }
        }
    }

    private function UpdateInputsProfile(): void
    {
        $inputs = json_decode($this->ReadAttributeString(self::ATTR_INPUTSMAPPING), true, 512, JSON_THROW_ON_ERROR);
        if (!IPS_VariableProfileExists("YAVR.Inputs{$this->InstanceID}")) {
            IPS_CreateVariableProfile("YAVR.Inputs{$this->InstanceID}", 1);
        }

        foreach ($inputs as $key => $data) {
            IPS_SetVariableProfileAssociation("YAVR.Inputs{$this->InstanceID}", $key, $data['title'], '', -1);
        }
    }

    public function RequestAction($ident, $value)
    {
        switch ($ident) {
            case self::VAR_STATE:
                $this->SetState($value);
                break;
            case self::VAR_SOUNDPROGRAM:
                if ($this->SetSoundProgram($value)) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_SCENE:
                $this->SetScene("Scene $value");
                break;
            case self::VAR_INPUT:
                $this->SetInput($this->GetInputKey($value));
                break;
            case self::VAR_MUTE:
                $this->SetMute($value);
                break;
            case self::VAR_VOLUME:
                $this->SetVolume($value);
                break;
            case self::VAR_PARTYMODE:
                if ($this->SetZoneParameter('setPartyMode', ['enable' => $value ? 'true' : 'false'])) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_PUREDIRECT:
                if ($this->SetZoneParameter('setPureDirect', ['enable' => $value ? 'true' : 'false'])) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_ENHANCER:
                if ($this->SetZoneParameter('setEnhancer', ['enable' => $value ? 'true' : 'false'])) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_TONECONTROL:
                if ($this->SetZoneParameter('setToneControl', ['enable' => $value ? 'true' : 'false'])) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_BASS:
                if ($this->SetZoneParameter(
                    'setToneControl',
                    ['mode' => 'manual', 'bass' => (int)($value * 2), 'treble' => (int)($this->GetValue(self::VAR_TREBLE)) * 2]
                )) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_TREBLE:
                if ($this->SetZoneParameter(
                    'setToneControl',
                    ['mode' => 'manual', 'treble' => (int)($value * 2), 'bass' => (int)($this->GetValue(self::VAR_BASS) * 2)]
                )) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_DIALOGUELEVEL:
                if ($this->SetZoneParameter('setDialogueLevel', ['value' => $value])) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_DIALOGUELIFT:
                if ($this->SetZoneParameter('setDialogueLift', ['value' => $value])) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_SUBWOOFERVOLUME:
                if ($this->SetZoneParameter('setSubwooferVolume', ['volume' => (int)($value * 2)])) {
                    $this->SetValue($ident, $value);
                }
                break;
            case self::VAR_SURROUNDAI:
                if ($this->SetZoneParameter('setSurroundAI', ['enable' => $value ? 'true' : 'false'])) {
                    $this->SetValue($ident, $value);
                }
                break;
            default:
                trigger_error('Unexpected ident: ' . $ident, E_USER_ERROR);
        }
    }

    public function RequestStatus(): bool
    {
        $remoteControl_BasicStatus = $this->Request('<Basic_Status>GetParam</Basic_Status>', 'GET');
        if ($remoteControl_BasicStatus === false) {
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

        $this->SetValue(self::VAR_STATE, $remoteControl_BasicStatus->Basic_Status->Power_Control->Power === 'On');

        $inputId = $this->GetInputId((string)$remoteControl_BasicStatus->Basic_Status->Input->Input_Sel);
        if ($inputId !== null) {
            $this->SetValue(self::VAR_INPUT, $inputId);
        }

        $this->SetValue(self::VAR_VOLUME, round($remoteControl_BasicStatus->Basic_Status->Volume->Lvl->Val / 10, 1));

        $this->SetValue(self::VAR_MUTE, $remoteControl_BasicStatus->Basic_Status->Volume->Mute == 'On');

        $remoteControl_ExtendedControlList = $this->RequestExtendedControlList('getStatus', 'GET', $this->GetYECZoneName(), '');

        if ($remoteControl_ExtendedControlList === null) {
            return false;
        }

        $this->SetValue(self::VAR_PARTYMODE, $remoteControl_ExtendedControlList['party_enable'] === ' true');

        $this->SetValue(self::VAR_PUREDIRECT, $remoteControl_ExtendedControlList['pure_direct'] === 'true');

        $this->SetValue(self::VAR_ENHANCER, $remoteControl_ExtendedControlList['enhancer'] === 'true');

        $this->SetValue(self::VAR_TONECONTROL, $remoteControl_ExtendedControlList['tone_control']['mode'] === 'manual');

        $this->SetValue(self::VAR_BASS, $remoteControl_ExtendedControlList['tone_control']['bass'] / 2);

        $this->SetValue(self::VAR_TREBLE, $remoteControl_ExtendedControlList['tone_control']['treble'] / 2);

        $this->SetValue(self::VAR_DIALOGUELEVEL, $remoteControl_ExtendedControlList['dialogue_level']);

        $this->SetValue(self::VAR_DIALOGUELIFT, $remoteControl_ExtendedControlList['dialogue_lift']);

        $this->SetValue(self::VAR_SUBWOOFERVOLUME, $remoteControl_ExtendedControlList['subwoofer_volume'] / 2);

        $this->SetValue(self::VAR_SURROUNDAI, $remoteControl_ExtendedControlList['surround_ai'] === 'true');

        return true;
    }

    protected function SetValue($ident, $value): bool
    {
        /** @noinspection TypeUnsafeComparisonInspection */
        if ($this->GetValue($ident) != $value) {
            return parent::SetValue($ident, $value);
        }

        return true;
    }

    public function Request(string $partial, string $cmd = 'GET')
    {
        $host   = $this->ReadPropertyString(self::PROP_HOST);
        $zone   = $this->ReadPropertyString('Zone');
        $cmd    = strtoupper($cmd);
        $xml    = '<?xml version="1.0" encoding="utf-8"?>';
        $xml    .= "<YAMAHA_AV cmd=\"{$cmd}\">";
        $xml    .= "<{$zone}>{$partial}</{$zone}>";
        $xml    .= '</YAMAHA_AV>';
        $client = curl_init();
        $url    = "http://$host:80/YamahaRemoteControl/ctrl";
        curl_setopt($client, CURLOPT_URL, "http://$host:80/YamahaRemoteControl/ctrl");
        curl_setopt($client, CURLOPT_USERAGENT, 'SymconYAVR');
        curl_setopt($client, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($client, CURLOPT_TIMEOUT, 2);
        curl_setopt($client, CURLOPT_POST, true);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($client, CURLOPT_POSTFIELDS, $xml);
        $this->SendDebug(__FUNCTION__, sprintf('url: %s, postfields: %s', $url, $xml), 0);
        $result       = curl_exec($client);
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

    private function RequestExtendedControlList(string $partialPath, string $method, string $zone, string $jsonData): ?array
    {
        $jsonReturn = $this->RequestExtendedControl($partialPath, $method, $zone, $jsonData);

        if ($jsonReturn === null) {
            return null;
        }

        return json_decode($jsonReturn, true, 512, JSON_THROW_ON_ERROR);
    }

    public function RequestExtendedControl(string $partialPath, string $method, string $zone, string $jsonData): ?string
    {
        if ($jsonData !== '') {
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $data = [];
        }

        $baseURL = sprintf('http://%s/YamahaExtendedControl/v1', $this->ReadPropertyString(self::PROP_HOST));

        if ($zone !== '') {
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
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);

        if (($method === 'POST') && count($data)) {
            curl_setopt($client, CURLOPT_POST, true);
            curl_setopt($client, CURLOPT_POSTFIELDS, $data);
            $this->SendDebug(__FUNCTION__, sprintf('url: %s, postfields: %s', $url, json_encode($data, JSON_THROW_ON_ERROR, 512)), 0);
        } else {
            $this->SendDebug(__FUNCTION__, sprintf('url: %s', $url), 0);
        }

        $result       = curl_exec($client);
        $responseCode = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
        curl_close($client);

        $this->SendDebug(__FUNCTION__, sprintf('ResponseCode: %s, result: %s', $responseCode, $result), 0);

        if ($responseCode !== 200) {
            return null;
        }

        return $result;
    }

    private function SetState(bool $state)
    {
        $this->SetValue(self::VAR_STATE, $state);
        $power = $state ? 'On' : 'Standby';
        return $this->Request("<Power_Control><Power>{$power}</Power></Power_Control>", 'PUT');
    }

    private function SetZoneParameter(string $partialPath, array $values): bool
    {
        $json = $this->RequestExtendedControl($partialPath, 'GET', $this->GetYECZoneName(), json_encode($values, JSON_THROW_ON_ERROR, 512));

        return !(($json === false) || json_decode($json, true, 512, JSON_THROW_ON_ERROR)['response_code'] !== 0);
    }

    private function SetSoundProgram(int $soundProgram): bool
    {
        $YECSoundProgram = self::SOUNDPROGRAMS[$soundProgram]['name'];

        return $this->RequestExtendedControlList(
                'setSoundProgram',
                'GET',
                $this->GetYECZoneName(),
                json_encode(['program' => $YECSoundProgram], JSON_THROW_ON_ERROR, 512)
            )['response_code'] === 0;
    }

    private function SetMute(bool $state)
    {
        $this->SetValue(self::VAR_MUTE, $state);
        $mute = $state ? 'On' : 'Off';
        return $this->Request("<Volume><Mute>{$mute}</Mute></Volume>", 'PUT');
    }

    public function SetScene(string $scene)
    {
        return $this->Request("<Scene><Scene_Sel>{$scene}</Scene_Sel></Scene>", 'PUT');
    }

    public function SetInput(string $input)
    {
        $this->SetValue(self::VAR_INPUT, $this->GetInputId($input));
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
        $this->SetValue(self::VAR_VOLUME, $volume);
        $volume *= 10;
        return $this->Request("<Volume><Lvl><Val>{$volume}</Val><Exp>1</Exp><Unit>dB</Unit></Lvl></Volume>", 'PUT');
    }

    private function GetYECZoneName(): string
    {
        switch ($this->ReadPropertyString(self::PROP_ZONE)) {
            case 'Main_Zone':
                return 'main';
            case 'Zone_2':
                return 'zone2';
            case 'Zone_3':
                return 'zone3';
            case 'Zone_4':
                return 'zone4';
            default:
                return '';
        }
    }

    public function UpdateScenes()
    {
        $result = [];
        $data   = $this->Request('<Scene><Scene_Sel_Item>GetParam</Scene_Sel_Item></Scene>', 'GET');
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

        foreach ((array)$data->Scene->Scene_Sel_Item as $id => $item) {
            $item = (array)$item;
            if ($item['RW'] === 'W') {
                $result[str_replace('Scene ', '', $item['Param'])] = htmlspecialchars_decode((string)$item['Title']);
            }
        }
        $this->WriteAttributeString(self::ATTR_SCENESMAPPING, json_encode($result, JSON_THROW_ON_ERROR, 512));
        $this->UpdateScenesProfile();

        $sceneId = $this->RegisterVariableInteger(self::VAR_SCENE, 'Szene', "YAVR.Scenes{$this->InstanceID}", 8);
        $this->EnableAction(self::VAR_SCENE);
        IPS_SetIcon($sceneId, 'HollowArrowRight');

        $resultText = "ID\tName\n";
        foreach ($result as $id => $data) {
            $resultText .= "$id\t{$data}\n";
        }
        return $resultText;
    }

    private function UpdateSoundPrograms(): bool
    {
        $data = $this->RequestExtendedControl('getSoundProgramList', 'GET', $this->GetYECZoneName(), '');
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        if ($data['response_code'] !== 0) {
            return false;
        }

        $this->UpdateSoundProgramsProfile($data['sound_program_list']);

        $this->RegisterVariableInteger(self::VAR_SOUNDPROGRAM, 'Sound Programm', "YAVR.SoundPrograms{$this->InstanceID}", 0);
        $this->EnableAction(self::VAR_SOUNDPROGRAM);

        return true;
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

        $result  = [];
        $counter = 0;

        foreach ((array)$data->Input->Input_Sel_Item as $id => $item) {
            $counter++;
            $item = (array)$item;
            if ($item['RW'] === 'RW') {
                $result[$counter] = [
                    'id'    => (string)$item['Param'],
                    'title' => (string)$item['Title']
                ];
            }
        }

        $this->WriteAttributeString(self::ATTR_INPUTSMAPPING, json_encode($result, JSON_THROW_ON_ERROR, 512));

        $this->UpdateInputsProfile();

        $inputId = $this->RegisterVariableInteger(self::VAR_INPUT, 'Eingang', "YAVR.Inputs{$this->InstanceID}", 9);
        $this->EnableAction(self::VAR_INPUT);
        IPS_SetIcon($inputId, 'ArrowRight');

        $resultText = "Symcon ID\tAVR Param\t\tTitel\n";
        foreach ($result as $id => $data) {
            $resultText .= "$id\t\t{$data['id']}\t\t{$data['title']}\n";
        }
        return $resultText;
    }
}
