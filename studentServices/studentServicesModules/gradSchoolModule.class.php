<?php
require_once("reqModule.template.php");

class gradSchoolModule extends reqModule
{
    protected $queue = 51;
    protected $boxProject = "gs_general_inquiry_51";

    private $gsConfigFile = "./studentServicesConfig/gs-support-config.csv";
    private $gsConfig = array();

    protected $classHeader = "gsc";
    protected $reqName = "Request Type";
    protected $reqDesc = "This is a default request type template.";
    protected $reqTags = "request,type,template";

    public function __construct($name="", $desc="", $tags="")
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

        // open config file
        $configStream = fopen($this->gsConfigFile, 'r');

        // skip config header
        $headers = fgetcsv($configStream);

        $config = array();
        // parse config file for request types
        while ($entry = fgetcsv($configStream)) {
            if (count($entry) >= 3) { // type, desc, tag(s)
                // get request type and desc
                $reqName = $entry[0];
                $reqDesc = $entry[1];
                $hide = $entry[2];
                $undergradHide = $entry[3];
                $reqTagString = implode('#', array_splice($entry, 4)); // tags in columns E and greater

                // format tags
                $reqTagString = strtolower($reqTagString);

                // add req type to list
                $reqType = array(
                    'ReqName' => $reqName,
                    'ReqDesc' => $reqDesc,
                    'Hide' => $hide,
                    'UndergradHide' => $undergradHide,
                    'ReqTagString' => $reqTagString,
                );
                array_push($config, $reqType);
            }
        }
        $this->gsConfig = $config;
        fclose($configStream);
    }

    public function finish()
    {
        Global $req;

        $subject = $_POST[$this->classHeader . 'Subject'] . " - " . $req['firstName'] . " " . $req['lastName'];
  
        $reqType = $_POST[$this->classHeader . "Type"];
        $body = "Request Type: $reqType\n\n";

        $queue = "";
        $boxProject = "";
        switch ($reqType) {
            case "ADMIT":
                $queue = 4819;
                $boxProject = "gs_admit_4819";
            break;

            case "Admissions":
                $queue = 328;
                $boxProject = "gs_admissions_328";
            break;

            case "Financial":
                $queue = 329;
                $boxProject = "gs_financial_329";
            break;

            case "Graduate Assistantship":
                $queue = 3769;
                $boxProject = "gs_ga_3769";
            break;

            case "IT-Support":
                $queue = 330;
                $boxProject = "gs_it_support_330";
            break;

            case "Progressions":
                $queue = 332;
                $boxProject = "gs_progressions_332";
            break;

            case "Recruitment":
                $queue = 333; 
                $boxProject = "gs_recruitment_333";
            break;

            case "Residency":
                $queue = 4820;
                $boxProject = "gs_residency_4820";
            break;

            case "General Inquiry":
                $queue = $this->queue;
                $boxProject = $this->boxProject;
            break;
        }

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
            hideRequiredField("<?php echo $this->classHeader;?>AffilRow");
            hideRequiredField("<?php echo $this->classHeader;?>StudentTypeRow");
            hideField("<?php echo $this->classHeader;?>TypeRow");
        }
        </script>
        <?php
    }

    public function js_UpdateFormControls()
    {
        ?>
        <script type="text/javaScript">
        let reqTypeList = <?php echo json_encode($this->gsConfig); ?>;

        function <?php echo $this->classHeader;?>UpdateFormControls()
        {
            showRequiredField("<?php echo $this->classHeader;?>SubjectRow");
            showRequiredField("<?php echo $this->classHeader;?>AffilRow");
            <?php echo $this->classHeader;?>UpdateReqType();
        }

        function <?php echo $this->classHeader;?>UpdateReqType() {
            showRequiredField("<?php echo $this->classHeader;?>SubjectRow");
            let affiliation = document.getElementById('<?php echo $this->classHeader;?>Affil').value;

            if (affiliation == "Student") {
                showRequiredField('<?php echo $this->classHeader;?>StudentTypeRow');
                hideField('<?php echo $this->classHeader;?>TypeRow');
            }
            else if (affiliation != "") {
                hideRequiredField('<?php echo $this->classHeader;?>StudentTypeRow');
                showField('<?php echo $this->classHeader;?>TypeRow');
            }

            for (let key in reqTypeList) {
                if (affiliation == "Staff" || affiliation == "Faculty") {
                    //if (reqTypeList[key]['Hide'] == "Y") {
                    document.getElementById(reqTypeList[key]['ReqName']).style.display = "";
                    //}
                }
                else if (affiliation == "Student") {
                    if (reqTypeList[key]['Hide'] == "Y" || reqTypeList[key]['UndergradHide'] == "Y") {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "none";
                    }
                    else {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "";
                    }
                }
                else {
                    if (reqTypeList[key]['Hide'] == "Y") {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "none";
                    }
                    else {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "";
                    }
                }
            }
            // This is a lazy work around
            if (affiliation == "Faculty") {
                document.getElementById('IT-Support').style.display = "none";
            }
        }

        function <?php echo $this->classHeader;?>UpdateUndergradReqType() {
            let studentType = document.getElementById('<?php echo $this->classHeader;?>StudentType').value;

            if (studentType != "") {
                showField('<?php echo $this->classHeader;?>TypeRow');
            }
            else {
                hideField('<?php echo $this->classHeader;?>TypeRow');
            }

            for (let key in reqTypeList) {
                if (studentType == "Graduate") {
                    if (reqTypeList[key]['Hide'] == "Y") {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "none";
                    }
                    else {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "";
                    }
                }
                else {
                    if (reqTypeList[key]['UndergradHide'] == "Y") {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "none";
                    }
                    else {
                        document.getElementById(reqTypeList[key]['ReqName']).style.display = "";
                    }
                }
            }

        }

        function <?php echo $this->classHeader;?>ClearReqType(searchStr) {
            document.getElementById('<?php echo $this->classHeader;?>Type').value = searchStr;

            hideField('<?php echo $this->classHeader;?>SelectInit');
            showField('<?php echo $this->classHeader;?>SelectChosen');
        }

        /************************************************************************
        // Finds minimum edit distance between two strings, used to find
        // 'likeness' in strings
        // Params: Two strings
        // Returns: The amount of edits to get from string a to string b
        ************************************************************************/
        function levenshteinDistance(a, b) {
            // Create empty edit distance matrix for all possible modifications of
            // substrings of a to substrings of b.
            const distanceMatrix = Array(b.length + 1).fill(null).map(() => Array(a.length + 1).fill(null));

            // Fill the first row of the matrix.
            // If this is first row then we're transforming empty string to a.
            // In this case the number of transformations equals to size of a substring.
            for (let i = 0; i <= a.length; i += 1) {
                distanceMatrix[0][i] = i;
            }

            // Fill the first column of the matrix.
            // If this is first column then we're transforming empty string to b.
            // In this case the number of transformations equals to size of b substring.
            for (let j = 0; j <= b.length; j += 1) {
                distanceMatrix[j][0] = j;
            }

            for (let j = 1; j <= b.length; j += 1) {
                for (let i = 1; i <= a.length; i += 1) {
                const indicator = a[i - 1] === b[j - 1] ? 0 : 1;
                distanceMatrix[j][i] = Math.min(
                    distanceMatrix[j][i - 1] + 1, // deletion
                    distanceMatrix[j - 1][i] + 1, // insertion
                    distanceMatrix[j - 1][i - 1] + indicator, // substitution
                    );
                }
            }

            return distanceMatrix[b.length][a.length];
        }

        function <?php echo $this->classHeader;?>ShouldShow(searchStr, tag) {
            let dict = {
                "gre": "progressions"
            };
            for (let key in dict) {
                value = dict[key];
                if (searchStr == key && tag == value) {
                    return false;
                }
            }
            return true;
        }

        /*** REQUEST TYPE SELECTION ***/
        function <?php echo $this->classHeader;?>FilterSelect() {
            let searchString = (document.getElementById('<?php echo $this->classHeader;?>SelectFilter').value).trim().toLowerCase();
            let searchTerms = searchString.split(" ");
            let reqSelectList = document.getElementsByName('<?php echo $this->classHeader;?>SelectList');
            let reqSelectListTags = document.getElementsByName('<?php echo $this->classHeader;?>SelectListTags');
            let affil = document.getElementById('<?php echo $this->classHeader;?>Affil').value;
            //console.log(affil);

            let k = 0;
            let found = true;
            let numSelect = reqSelectList.length;
            let numSearchTerms = searchTerms.length;
            for(let i = 0; i < numSelect; i++) {
                let currTagList = (reqSelectListTags[i].value).split("#");
                let hideFromUser = false;

                //console.log(reqTypeList[i]);
                //console.log(reqSelectList[i]);
                if (affil != "Staff") {
                    if (reqTypeList[i]['Hide'] == "Y") {
                        hideFromUser = true;
                    }
                    else {
                        hideFromUser = false;
                    }
                }
                else {
                    hideFromUser = false
                }

                k = 0;
                found = true;
                while (k < numSearchTerms && found === true) {
                    found = reqSelectListTags[i].value.includes(searchTerms[k]);
                    k++;
                }

                if (found === true && hideFromUser == false) {
                    reqSelectList[i].style.display = "";
                }
                else {
                    reqSelectList[i].style.display = "none";
                }
            }
        }

        function <?php echo $this->classHeader;?>MakeSelect(reqElt) {
            if(reqElt) {
                document.getElementById('<?php echo $this->classHeader;?>Type').value = reqElt.innerText;

                hideField('<?php echo $this->classHeader;?>SelectInit');
                showField('<?php echo $this->classHeader;?>SelectChosen');
            }
        }

        function <?php echo $this->classHeader;?>UndoSelect() {
            document.getElementById('<?php echo $this->classHeader;?>Type').value = "";
            <?php echo $this->classHeader;?>DisplayInfo(null); // default description

            showField('<?php echo $this->classHeader;?>SelectInit');
            hideField('<?php echo $this->classHeader;?>SelectChosen');
        }

        function <?php echo $this->classHeader;?>DisplayInfo(reqElt) {
            // Get new description
            let newDesc = "";
            if(reqElt) {
                let str = (reqElt.value).replace("+", ", ");
                while (str.includes("+")) {
                    str = str.replace("+", ", ");
                }
                newDesc = "<strong>"+reqElt.innerText+"</strong><hr />"+"<p>"+str+"</p>";
            }
            else {
                newDesc = "<strong>--</strong><hr /><p>Highlight a category to the left for more information.</p>"; // default description
            }

            // Display description
            document.getElementById('<?php echo $this->classHeader;?>SelectDesc').innerHTML = newDesc;
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

            <div class="row mb-3" id="<?php echo $this->classHeader;?>AffilRow">
                <div class="col-md-4 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>Affil" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>Affil">Requestor's Affiliation: </label>
                </div>
                <div class="col-md-6 col-lg-6">
                    <select name="<?php echo $this->classHeader;?>Affil" id="<?php echo $this->classHeader;?>Affil" class="form-select" onchange="<?php echo $this->classHeader;?>UpdateReqType();" required="">
                        <option value="" selected="selected">-- Select an affiliation --</option>
                        <option value="Student">Student</option>
                        <option value="Staff">Staff</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Alumni">Alumni</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>StudentTypeRow" style="display: none;">
                <div class="col-md-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>StudentType" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>StudentType">Type of Student: </label>
                </div>
                <div class="col-md-8 col-lg-6">
                    <select name="<?php echo $this->classHeader;?>StudentType" id="<?php echo $this->classHeader;?>StudentType" class="form-select" onchange="<?php echo $this->classHeader;?>UpdateUndergradReqType();" required="">
                        <option value="" selected="selected">-- Select Student Type --</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Undergraduate">Undergraduate</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>TypeRow" >
                <div class="col-md-2 col-lg-2 col-form-label">
                    <label class="form-label" for="<?php echo $this->classHeader;?>Type"><span class="req-span">*&nbsp;</span>I need help with:</label>
                </div>
                <div class="col-md-8 col-lg-6" id="<?php echo $this->classHeader;?>SelectChosen" style="display:none">
                    <div class="input-group">
                        <input id="<?php echo $this->classHeader;?>Type" name="<?php echo $this->classHeader;?>Type" class="form-control" readonly required />
                        <button class="btn btn-secondary" type="button" onclick="<?php echo $this->classHeader;?>UndoSelect();">Change</button>
                    </div>
                </div>

                <div class="col-md-8 col-lg-6" id="<?php echo $this->classHeader;?>SelectInit">
                    <div class="input-group">
                        <div class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter" viewBox="0 0 16 16">
                                <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        </div>
                        <input id="<?php echo $this->classHeader;?>SelectFilter" name="<?php echo $this->classHeader;?>SelectFilter" type="text" class="form-control" onkeyup="<?php echo $this->classHeader;?>FilterSelect();" placeholder="Filter topics..." />
                    </div>

                    <div class="container-fluid pt-1 pb-1 border-end border-start border-bottom rounded-bottom" >
                        <div class="row">
                            <div class="col-6 border-end">
                                <div class="list-group list-group-flush px-3" style="height: 260px; overflow-y: auto">
                                    <?php
                                        for ($i = 0; $i < count($this->gsConfig); $i++) {
                                            if ($this->gsConfig[$i]['Hide'] == 'Y' || $this->gsConfig[$i]['UndergradHide'] == 'Y') {
                                                echo "<button type=\"button\" ";
                                                echo "class=\"list-group-item list-group-item-action\" onclick=\"" . $this->classHeader . "MakeSelect(this)\" name=\"" . $this->classHeader . "SelectList\" id=\"".$this->gsConfig[$i]['ReqName']."\"value=\"" . $this->gsConfig[$i]['ReqDesc'] . "\" onmouseover=\"" . $this->classHeader . "DisplayInfo(this)\" style=\"display: none;\">";
                                                echo $this->gsConfig[$i]['ReqName'] . "</button>\n";

                                                echo "<input name=\"" . $this->classHeader . "SelectListTags\" id=\"".$this->gsConfig[$i]['ReqName']."-tags\" value=\"".$this->gsConfig[$i]['ReqTagString']."\" readonly hidden/>";
                                            }
                                            else {
                                                echo "<button type=\"button\" ";
                                                echo "class=\"list-group-item list-group-item-action\" onclick=\"" . $this->classHeader . "MakeSelect(this)\" name=\"" . $this->classHeader . "SelectList\" id=\"".$this->gsConfig[$i]['ReqName']."\"value=\"" . $this->gsConfig[$i]['ReqDesc'] . "\" onmouseover=\"" . $this->classHeader . "DisplayInfo(this)\" style=\"display: \"\";\">";
                                                echo $this->gsConfig[$i]['ReqName'] . "</button>\n";

                                                echo "<input name=\"" . $this->classHeader . "SelectListTags\" id=\"".$this->gsConfig[$i]['ReqName']."-tags\" value=\"".$this->gsConfig[$i]['ReqTagString']."\" readonly hidden/>";
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div id="<?php echo $this->classHeader;?>SelectDesc">
                                    <strong>--</strong>
                                    <hr />
                                    <p>Highlight a category to the left for more information.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}