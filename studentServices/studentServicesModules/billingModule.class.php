<?php
require_once("reqModule.template.php");

class billingModule extends reqModule
{
    protected $queue = 888;
    protected $boxProject = "student_billing_888";

    protected $classHeader = "bll";
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

        $service = $_POST[$this->classHeader . "Services"];
        $body .= "Reason for request:    " . $service . "\n\n";

        $subject = filter_input(INPUT_POST, $this->classHeader . "Subject", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $subject .= " - " . $req['FirstName'] . " " . $req['LastName'];

        $tags = array("!fns=studentfaculty");

        $customFields = array(
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
            hideRequiredField("<?php echo $this->classHeader;?>ServiceRow");
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
            showRequiredField("<?php echo $this->classHeader;?>ServiceRow");
        }
        </script>
        <?php
    }

    public function html_outputFields()
    {
        ?>
        <div class="container" id="<?php echo $this->classHeader;?>Container">
            <div class="row mb-3" id="<?php echo $this->classHeader;?>ServiceRow">
                <div class="col-md-4 col-lg-3 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Service" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Service">Reason for your request:</label>
                </div>
                <div class="col-md-6 col-lg-5">
                    <select id="<?php echo $this->classHeader;?>Service" name="<?php echo $this->classHeader;?>Service" class="form-select">
                        <option value="" selected>--Please select an option--</option>
                        <option value="Refund">Refund</option>
                        <option value="Billing">Billing</option>
                        <option value="Late Fee">Late Fee</option>
                        <option value="Monthly Payment Plan">Monthly Payment Plan</option>
                        <option value="Perkins Loan">Perkins Loan</option>
                        <option value="Registration Issue">Registration Issue</option>
                        <option value="Account in Collections">Account in Collections</option>
                        <option value="Third Party Payments">Third Party Payments</option>
                        <option value="Tuition Remission">Tuition Remission</option>
                        <option value="VA/GI Payments">VA/GI Payments</option>
                        <option value="1098T">1098T</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }
}