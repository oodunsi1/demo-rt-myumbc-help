<?php
require_once("reqModule.template.php");

class degreePlannerModule extends reqModule
{
    protected $queue = 2862;
    protected $boxProject = "doit-test-folder";

    protected $classHeader = "dpn";
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

        $tags = array("!fns=studentfaculty");

        $customFields = array(
            $tagCF => $tags,
        );

        $body = "";

        $requestorType = $_POST[$this->classHeader . "RequestorType"];
        $requestType = $_POST[$this->classHeader . 'Type'];

        $subject = "Degree Planner--$requestType--"; 
        $subject .= " - " . $req['FirstName'] . " " . $req['LastName'];

        $body .= "Requestor Type:               " . $requestorType . "\n";
        $body .= "Request Type:                 " . $requestType . "\n\n";

        $customFields[2978] = $requestType;
        $customFields[2979] = $requestorType;
        
        switch($requestType)
        {
            case "Academic Advising Question":
                $question = filter_input(INPUT_POST, $this->classHeader . "AdvisingQuestion", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                $body .= $question . "\n\n";
            break;
                
            case "Technical Issue":
                $techIssueType = $_POST[$this->classHeader . "TechIssueType"];
                $techIssue = filter_input(INPUT_POST, $this->classHeader . "TechIssue", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                
                $body .= "Tech issue type:              " . $techIssueType . "\n";
                $body .= "Tech issue:\n" . $techIssue . "\n\n";

                $customFields[2980] = $techIssueType;
            break;

            case "Training":
                $message = filter_input(INPUT_POST, $this->classHeader . "TrainingMessage", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                $body .= $message . "\n\n";
            break;
        }

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
            hideRequiredField("<?php echo $this->classHeader;?>RequestorTypeRow");
            hideRequiredField("<?php echo $this->classHeader;?>TypeRow");
            hideRequiredField("<?php echo $this->classHeader;?>AdvisingQuestionRow");
            hideRequiredField("<?php echo $this->classHeader;?>TechIssueTypeRow");
            hideRequiredField("<?php echo $this->classHeader;?>TechIssueRow");
            hideRequiredField("<?php echo $this->classHeader;?>TrainingMessageRow");
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
            
            showRequiredField("<?php echo $this->classHeader;?>RequestorTypeRow");
            showRequiredField("<?php echo $this->classHeader;?>TypeRow");

            let type = document.getElementById("<?php echo $this->classHeader;?>Type").value;
            switch (type)
            {
                case "Academic Advising Question":
                    showRequiredField("<?php echo $this->classHeader;?>AdvisingQuestionRow");
                break;

                case "Technical Issue":
                    showRequiredField("<?php echo $this->classHeader;?>TechIssueTypeRow");
                    showRequiredField("<?php echo $this->classHeader;?>TechIssueRow");
                break;

                case "Training":
                    showRequiredField("<?php echo $this->classHeader;?>TrainingMessageRow");
                break;

                default:
                break;
            }
        }
        </script>
        <?php
    }

    public function html_outputFields()
    {
        ?>
        <div class="container" id="<?php echo $this->classHeader;?>Container">
            <div class="row mb-3" id="<?php echo $this->classHeader;?>RequestorTypeRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>RequestorType" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>RequestorType">You are a:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>RequestorType" name="<?php echo $this->classHeader;?>RequestorType" class="form-select">
                        <option value="" selected>-- Choose --</option>
                        <option value='Student'>Student</option>
                        <option value='Staff/Faculty'>Staff/Faculty</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>TypeRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Type" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Type">Nature of Request:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>Type" name="<?php echo $this->classHeader;?>Type" class="form-select" onchange="updateFormControls();">
                        <option value="" selected>-- Choose --</option>
                        <option value="Academic Advising Question">Academic Advising Question</option>
                        <option value="Technical Issue">Technical Issue</option>
                        <option value="Training">Training</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>AdvisingQuestionRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>AdvisingQuestion" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>AdvisingQuestion">Question:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <small class="form-text text-muted">i.e.:What is the degree planner tool, and what can students do with it?</small>
                    <textarea id="<?php echo $this->classHeader;?>AdvisingQuestion" name="<?php echo $this->classHeader;?>AdvisingQuestion" class="form-control" rows="4" cols="45"></textarea>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>TechIssueTypeRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>TechIssueType" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>TechIssueType">Feature:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <small class="form-text text-muted">Please select the feature you are having trouble with.</small>
                    <select id="<?php echo $this->classHeader;?>TechIssueType" name="<?php echo $this->classHeader;?>TechIssueType" class="form-select">
                        <option value="" selected>-- Choose --</option>
                        <option value="Degree Planner">Degree Planner</option>
		                <option value="Degree Progress Donut View">Degree Progress Donut View</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>TechIssueRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>TechIssue" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>TechIssue">Issue:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <small class="form-text text-muted">In as much detail as possible, please describe for us the issue you were experiencing when viewing the degree planner.</small>
                    <textarea id="<?php echo $this->classHeader;?>TechIssue" name="<?php echo $this->classHeader;?>TechIssue" class="form-control" rows="4" cols="45"></textarea>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>TrainingMessageRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>TrainingMessage" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>TrainingMessage">Message:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <small class="form-text text-muted">What type of training would you like to see regarding the usage of these two new features?</small>
                    <textarea id="<?php echo $this->classHeader;?>TrainingMessage" name="<?php echo $this->classHeader;?>TrainingMessage" class="form-control" rows="4" cols="45"></textarea>
                </div>
            </div>
            
        </div>
        <?php
    }
}