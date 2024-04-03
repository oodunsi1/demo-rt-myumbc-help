<?php
require_once("reqModule.template.php");

class transcriptModule extends reqModule
{
    protected $queue = 348;
    protected $boxProject = "rr_transcripts_348";

    protected $classHeader = "tsc";
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

    /*public function finish($queue="", $boxProject="")
    {
        Global $req;
        if (empty($queue))
        {
            $queue = $this->queue;
        }
        if (empty($boxProject))
        {
            $boxProject = $this->boxProject;
        }

        $body = "";

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
    }*/

    /*public function js_Hide()
    {
        ?>
        <script type="text/javaScript">
        function <?php echo $this->classHeader;?>Hide()
        {
            hideRequiredField("<?php echo $this->classHeader;?>SubjectRow");
        }
        </script>
        <?php
    }*/

    /*public function js_UpdateFormControls()
    {
        ?>
        <script type="text/javaScript">
        function <?php echo $this->classHeader;?>UpdateFormControls()
        {
            showRequiredField("<?php echo $this->classHeader;?>SubjectRow");
        }
        </script>
        <?php
    }*/

    /*public function html_outputFields()
    {
        ?>
        <div class="container" id="<?php echo $this->classHeader;?>Container">
            <div class="form-row form-group" id="<?php echo $this->classHeader;?>SubjectRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Subject" class="req-span">*</span>
                    <label for="<?php echo $this->classHeader;?>Subject">Subject:</label>
                </div>
                <input id="<?php echo $this->classHeader;?>Subject" name="<?php echo $this->classHeader;?>Subject" type="text" class="col-md-7 col-lg-6 form-control" />
            </div>
        </div>
        <?php
    }*/
}