<?php

class reqModule
{
    protected $queue = 4131;
    protected $boxProject = "doit-test-folder";

    protected $classHeader = "req";
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

    public static function checkCampusID()
    {

    }

    public static function js_checkCampusID()
    {
        ?>
        <script type="text/javaScript">
        </script>
        <?php
    }

    public static function formatCurrency($target)
    {
        return $target.toMoney(2);
    }

    public static function js_formatCurrency()
    {
        ?>
        <script type="text/javaScript">
        function formatCurrency(target)
        {
            target.value = parseFloat(target.value).toFixed(2);
        }
        </script>
        <?php
    }

    public static function js_outputFlowControl()
    {
        ?>
        <script type="text/javaScript">
        function hideField(eltID)
        {
            var divElt = document.getElementById(eltID);
            divElt.style.display = "none";
        }
        function showField(eltID)
        {
            var divElt = document.getElementById(eltID);
            divElt.style.display = "";
        }

        function hideRequiredField(eltID)
        {
            var divElt = document.getElementById(eltID);
            divElt.style.display = "none";
            unsetRequired(eltID);
        }
        function showRequiredField(eltID)
        {
            var divElt = document.getElementById(eltID);
            divElt.style.display = "";
            setRequired(eltID);
        }

        function hideRequiredFieldGroup(eltID)
        {
            var divElt = document.getElementById(eltID);
            divElt.style.display = "none";

            var eltName = eltID.slice(0, eltID.length-3);
            var fields = document.getElementsByName(eltName);
            if (fields.length <= 0)
            {
                fields = document.getElementsByName(eltName+"[]");
            }
            fields.forEach(function(e) {
                e.required = false;
            });
            var spn = document.getElementById("spn-" + eltName);
            spn.style.display = "none";
        }
        function showRequiredFieldGroup(eltID)
        {
            var divElt = document.getElementById(eltID);
            divElt.style.display = "";

            var eltName = eltID.slice(0, eltID.length-3);
            var fields = document.getElementsByName(eltName);
            if (fields.length <= 0)
            {
                fields = document.getElementsByName(eltName+"[]");
            }
            fields.forEach(function(e) {
                e.required = true;
            });
            var spn = document.getElementById("spn-" + eltName);
            spn.style.display = "";
        }

        function unsetRequired(eltID)
        {
            var actual = eltID.slice(0, eltID.length-3);
            var divElt = document.getElementById(actual);
            divElt.required = false;

            divElt = document.getElementById("spn-" + actual);
            divElt.style.display = "none";
        }
        function setRequired(eltID)
        {
            var actual = eltID.slice(0, eltID.length-3);
            var divElt = document.getElementById(actual);
            divElt.required = true;

            divElt = document.getElementById("spn-" + actual);
            divElt.style.display = "";
        }
        </script>
        <?php
    }

    public function getClassHeader()
    {
        return $this->classHeader;
    }

    public function setClassHeader($classHeader)
    {
        $this->classHeader = $classHeader;
    }

    public function finish($queue="", $boxProject="")
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
        //$subject .= " - " . $req['FirstName'] . " " . $req['LastName'];

        $tags = array("!fns=umbcHelp", "!src=Computing&Technology");

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
                    <label for="<?php echo $this->classHeader;?>Subject">Subject:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input id="<?php echo $this->classHeader;?>Subject" name="<?php echo $this->classHeader;?>Subject" type="text" class="form-control" />
                </div>
            </div>
        </div>
        <?php
    }
}