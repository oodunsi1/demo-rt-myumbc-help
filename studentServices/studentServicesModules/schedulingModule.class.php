<?php
require_once("reqModule.template.php");

class schedulingModule extends reqModule
{
    protected $queue = 188;
    protected $boxProject = "rr_academic_dept_soc_changes_188";

    protected $classHeader = "soc";
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

        $term = $_POST[$this->classHeader . "Term"];
        $body .= "Term: " . $term . "\n\n";

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
            hideRequiredField("<?php echo $this->classHeader;?>SubjectRow");
            hideRequiredField("<?php echo $this->classHeader;?>TermRow");
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
            showRequiredField("<?php echo $this->classHeader;?>SubjectRow");
            showRequiredField("<?php echo $this->classHeader;?>TermRow");
        }
        </script>
        <?php
    }

    public function html_outputFields()
    {
        ?>
        <div class="container" id="<?php echo $this->classHeader;?>Container">
            <div class="row mb-3" id="<?php echo $this->classHeader;?>SubjectRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Subject" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Subject">Subject:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input id="<?php echo $this->classHeader;?>Subject" name="<?php echo $this->classHeader;?>Subject" type="text" class="form-control" />
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>TermRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Term" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Term">Term:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>Term" name="<?php echo $this->classHeader;?>Term" class="form-select">
                        <option value="" selected>--Please select an option--</option>
                        <option value="Fall">Fall</option>
                        <option value="Spring">Spring</option>
                        <option value="Winter">Winter</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }
}