<?php
require_once("reqModule.template.php");

class UGAdmissionsModule extends reqModule
{
    protected $queue = 49;
    protected $boxProject = "ugrad_admissions_49";

    protected $classHeader = "uga";
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

        $reqType = $_POST[$this->classHeader . 'Service'];
        switch($reqType)
        {
            case "Application Status":
                $queue = 1409;
            break;

            case "Freshman Admissions":
                $queue = 1431;
            break;

            case "Transfer Admissions":
                $queue = 1473;
            break;

            case "International Admissions":
                $queue = 1432;
            break;

            case "Orientation and Placement Testing":
                $queue = 1469;
            break;

            case "Updating Personal Information":
                $queue = 1475;
            break;

            case "Visiting UMBC":
                $queue = 1476;
            break;

            case "Material Request":
                $queue = 1468;
            break;

            case "Unsubscribe":
                $queue = 1474;
            break;

            default:
                $queue = $this->queue;
            break;
        }

        $affiliate = $_POST[$this->classHeader . 'Affiliate'];
        $nameList = $_POST[$this->classHeader . 'Name'];
        $firstName = filter_var($nameList[0], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $lastName = filter_var($nameList[1], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $dob = $_POST[$this->classHeader . 'DOB'];
        $email = filter_var($this->classHeader . 'Email', FILTER_SANITIZE_EMAIL);
        $phone = filter_var($this->classHeader . 'Phone', FILTER_SANITIZE_URL);

        $body  = "First Name:                " . $firstName . "\n";
        $body .= "Last Name:                 " . $lastName . "\n";
        $body .= "Date of Birth:             " . $dob . "\n\n";
        $body .= "Services:                  " . $reqType . "\n";
        $body .= "Affiliate:                 " . $affiliate . "\n\n";
        $body .= "Email:                     " . $email . "\n\n";
        $body .= "Phone:                     " . $phone . "\n\n";

        $customFields = array(
            1114 => $_POST['services'],   //X-UMBC-RequestType
            1194 => $_POST['affiliate'],  //X-UMBC-Affiliation
        );

        $subject = $reqType . " - " . $req['FirstName'] . " " . $req['LastName'];

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
        Global $authenticated;
        ?>
        <script type="text/javaScript">
        function <?php echo $this->classHeader;?>Hide()
        {
            hideRequiredField("<?php echo $this->classHeader;?>ServiceRow");
            hideRequiredField("<?php echo $this->classHeader;?>AffiliateRow");
            hideRequiredField("<?php echo $this->classHeader;?>DOBRow");
            hideRequiredField("<?php echo $this->classHeader;?>PhoneRow");
        <?php
        if (!$authenticated)
        {
        ?>
            hideRequiredFieldGroup("<?php echo $this->classHeader;?>NameRow");
            hideRequiredField("<?php echo $this->classHeader;?>EmailRow");
            hideRequiredField("<?php echo $this->classHeader;?>ConfirmEmailRow");
        <?php
        }
        ?>
        }
        </script>
        <?php
    }

    public function js_UpdateFormControls()
    {
        Global $authenticated;
        ?>
        <script type="text/javaScript">
        function <?php echo $this->classHeader;?>UpdateFormControls()
        {
            showRequiredField("<?php echo $this->classHeader;?>ServiceRow");
            showRequiredField("<?php echo $this->classHeader;?>AffiliateRow");
            showRequiredField("<?php echo $this->classHeader;?>DOBRow");
            showRequiredField("<?php echo $this->classHeader;?>PhoneRow");
        <?php
        if (!$authenticated)
        {
        ?>
            showRequiredFieldGroup("<?php echo $this->classHeader;?>NameRow");
            showRequiredField("<?php echo $this->classHeader;?>EmailRow");
            showRequiredField("<?php echo $this->classHeader;?>ConfirmEmailRow");
        <?php
        }
        ?>
        }

        <?PHP
        if (!$authenticated)
        {
        ?>
        function <?php echo $this->classHeader;?>ValidateEmail()
        {
            let em1 = document.getElementById("<?php echo $this->classHeader;?>Email");
            let em2 = document.getElementById("<?php echo $this->classHeader;?>ConfirmEmail");

            $(em1).removeClass("is-invalid");
            $(em1).removeClass("is-valid");
            $(em2).removeClass("is-invalid");
            $(em2).removeClass("is-valid");

            if (em1.value == em2.value)
            {
                $(em1).addClass("is-valid")
                $(em2).addClass("is-valid")
                return true;
            }
            else
            {
                //$(em1).addClass("is-invalid");
                $(em2).addClass("is-invalid");
            }
        }
        <?php
        }
        ?>
        </script>
        <?php
    }

    public function html_outputFields()
    {
        Global $authenticated;
        ?>
        <div class="container" id="<?php echo $this->classHeader;?>Container">
            <div class="row mb-3" id="<?php echo $this->classHeader;?>ServiceRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Service" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Service">I have a question about:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>Service" name="<?php echo $this->classHeader;?>Service" class="form-select">
                        <option value="" selected>-- Please select an option --</option>
                        <option value="Application Status">Application Status</option>
                        <option value="Freshman Admissions">Freshman Admissions</option>
                        <option value="International Admissions">International Admissions</option>
                        <option value="Transfer Admissions">Transfer Admissions</option>
                        <option value="Orientation and Placement Testing">Orientation and Placement Testing</option>
                        <option value="Updating personal information (phone, email, mailing address)">Updating personal information (phone, email, mailing address)</option>
                        <option value="Visiting UMBC">Visiting UMBC</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>AffiliateRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Affiliate" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Affiliate">I am a:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>Affiliate" name="<?php echo $this->classHeader;?>Affiliate" class="form-select">
                        <option value="" selected>-- Please select an option --</option>
                        <option value="Prospective UMBC Student">Prospective UMBC Student</option>
                        <option value="Admitted UMBC Student">Admitted UMBC Student</option>
                        <option value="Current UMBC Student">Current UMBC Student</option>
                        <option value="Parent/ Family Member">Parent/ Family Member</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

        <?php
        if (!$authenticated)
        {
        ?>
            <div class="row mb-3" id="<?php echo $this->classHeader;?>NameRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Name" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Name">Name:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <div class="input-group px-0">
                        <input id="<?php echo $this->classHeader;?>FirstName" name="<?php echo $this->classHeader;?>Name[]" type="text" class="form-control" placeholder="First Name" />
                        <input id="<?php echo $this->classHeader;?>LastName" name="<?php echo $this->classHeader;?>Name[]" type="text" class="form-control" placeholder="Last Name" />
                    </div>
                </div>
            </div>
        <?php
        }
        ?>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>DOBRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>DOB" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>DOB">Date of Birth:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input id="<?php echo $this->classHeader;?>DOB" name="<?php echo $this->classHeader;?>DOB" type="date" class="form-control" />
                </div>
            </div>

        <?php
        if (!$authenticated)
        {
        ?>
            <div class="row mb-3" id="<?php echo $this->classHeader;?>EmailRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Email" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Email">Email:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input id="<?php echo $this->classHeader;?>Email" name="<?php echo $this->classHeader;?>Email" type="email" class="form-control" onchange="<?php echo $this->classHeader;?>ValidateEmail();" />
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>ConfirmEmailRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>ConfirmEmail" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>ConfirmEmail">Confirm Email:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input id="<?php echo $this->classHeader;?>ConfirmEmail" name="<?php echo $this->classHeader;?>ConfirmEmail" type="email" class="form-control" onchange="<?php echo $this->classHeader;?>ValidateEmail();" />
                </div>
            </div>
        <?php
        }
        ?>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>PhoneRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Phone" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Phone">Phone #:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input id="<?php echo $this->classHeader;?>Phone" name="<?php echo $this->classHeader;?>Phone" type="tel" class="form-control" />
                </div>
            </div>
        </div>
        <?php
    }
}