<?php
require_once("reqModule.template.php");

class campusCardModule extends reqModule
{
    protected $queue = 868;
    protected $boxProject = "ccms_campus_card_mail_services_868";

    protected $classHeader = "ccd";
    protected $reqName = "Request Type";
    protected $reqDesc = "This is a default request type template.";
    protected $reqTags = "request,type,template";

    /*public function __construct($name="", $desc="", $tags="")
    {
        if (!empty($name))
        {
            $this->reqName = $name;
        }

        if (!empty($desc))
        {
            $this->reqDesc = $desc;
        }

        if (!empty($tags))
        {
            $this->reqTags = $tags;
        }
    }*/

    public function finish()
    {
        Global $req;
        $queue = $this->queue;
        $boxProject = $this->boxProject;

        $body = "";

        $ccdType = $_POST[$this->classHeader . "Type"];
        $body .= "Campus Card request type: " . $ccdType . "\n\n";

        $subject = filter_input(INPUT_POST, $this->classHeader . "Subject", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $subject .= " - " . $req['FirstName'] . " " . $req['LastName'];

        $tags = array("!fns=studentfaculty");

        $customFields = array(
            '194' => $requestType,
            '3019' => "Web",
            $tagCF => $tags,
        );

        $toReturn = array(
            "Queue" => $queue,
            "Subject" => $subject,
            "CustomFields" => $customFields,
            "BoxProject" => $boxProject,
            "BodyMid" => $body,
        );

        return $toReturn;
    }

    public function js_Hide()
    {
        ?>
        <script type="text/javaScript">
        function <?php echo $this->classHeader;?>Hide()
        {
            hideRequiredField("<?php echo $this->classHeader;?>TypeRow");
            hideRequiredField("<?php echo $this->classHeader;?>SubjectRow");
        }
        </script>
        <?php
    }

    public function js_UpdateFormControls()
    {
        ?>
        <script type="text/javaScript">
        function <?php echo $this->classHeader;?>UpdateFormControls()
        {
            showRequiredField("<?php echo $this->classHeader;?>TypeRow");
            showRequiredField("<?php echo $this->classHeader;?>SubjectRow");
        }
        </script>
        <?php
    }

    public function html_outputFields()
    {
        ?>
        <div class="container" id="<?php echo $this->classHeader;?>Container">
            <div class="row mb-3" id="<?php echo $this->classHeader;?>TypeRow">
                <div class="col-md-4 col-lg-3 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Type" style='color:#f00;'>*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Type">Campus Card Request Type:</label>
                </div>
                <div class="col-md-6 col-lg-5">
                    <select name="<?php echo $this->classHeader;?>Type" id="<?php echo $this->classHeader;?>Type" class="form-select" onchange='UpdateFormControls();' required>
                        <option value="" selected>(Select a value)</option>
                        <option value="Campus Card Id">Campus Card Id</option>
                        <option value="Vending">Vending</option>
                        <option value="Mail Services &#40;Departmental Mailing&#41;">Mail Services &#40;Departmental Mailing&#41;</option>
                        <option value="Personal Mailing">Personal Mailing</option>
                        <option value="DCARD">DCARD</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3" id="<?php echo $this->classHeader;?>SubjectRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Subject" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Subject">Subject:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input id="<?php echo $this->classHeader;?>Subject" name="<?php echo $this->classHeader;?>Subject" type="text" class="form-control" />
                </div>
            </div>
        </div>
        <?php
    }
}