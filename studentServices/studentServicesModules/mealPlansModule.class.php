<?php
require_once("reqModule.template.php");

class mealPlansModule extends reqModule
{
    protected $queue = 869;
    protected $boxProject = "ccms_meal_plans_869";

    protected $classHeader = "mpl";
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

        $tags = array("!fns=studentfaculty");

        $customFields = array(
            $tagCF => $tags,
        );

        $semester = $_POST[$this->classHeader . 'RequestedSemester'];
        $resLoc = $_POST[$this->classHeader . 'ResidenceLocation'];
        $term = $semester[0];
        $termYear = $semester[1];
        $termSemCode = '2' . substr($termYear,2,2);

        switch ($term)
        {
            case 'Fall':
                $termSemCode.='8';
            break;

            case 'Winter':
                $termSemCode.='0';
            break;

            case 'Spring':
                $termSemCode.='2';
            break;

            case 'Summer':
                $termSemCode.='6';
            break;
        }

        $tempArr = explode(',',$resLoc);
        $resCode = $tempArr[0];
        $resName = $tempArr[1];
        $mealPlan = '';
        $foodFundAmount = '';
        $requestTypeOther = '';

        if (isset($_POST[$this->classHeader . 'FoodFundAmount']))
        {
            $foodFundAmount = filter_input(INPUT_POST, $this->classHeader . 'FoodFundAmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        $requestType = $_POST[$this->classHeader . 'RequestType'];
        $subject = $requestType;

        if ($requestType == 'Other' && isset($_POST[$this->classHeader . 'OtherRequestType']))
        {
            $requestTypeOther = filter_input(INPUT_POST, $this->classHeader . 'OtherRequestType', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $subject .= "-$requestTypeOther";
        }

        $body  = "First Name:                  " . $req['FirstName'] . "\n";
        $body .= "Last Name:                   " . $req['LastName'] . "\n";
        $body .= "EMail:                       " . $req['Email'] ."\n\n";
        $body .= "Student ID:                  " . $req['CampusID'] ."\n\n";
        $body .= "Semester:                    " . $term .",".$termYear."\n\n";
        $body .= "Term Code:                   " . $termSemCode . "\n\n";
        $body .= "Request Type:                " . $requestType ."\n\n";
        if (strlen($requestTypeOther) > 0)
        {
            $body .= "Other Request Type:          " . $requestTypeOther ."\n\n";
        }
        if ($requestType != "Billing Inquiries" && $requestType != "Inquiry Only" && $requestType != "Other")
        {
            $body .= "Residence:                   " . $resName ."\n\n";
        }

        if ($term == 'Fall' || $term == 'Spring')
        {
            if ($resCode == 'C' || $resCode == 'A')
            {
                $mealPlan = $_POST[$this->classHeader . 'SFPlans'];
            }
            else if ($resCode == 'D')
            {
                $mealPlan = $_POST[$this->classHeader . 'SFDormPlans'];
            }
        }
        else
        {
            if ($term === 'Winter')
            {
                $mealPlan = $_POST[$this->classHeader . 'WinterPlans'];
            }
            else if ($term === 'Summer')
            {
                $mealPlan = $_POST[$this->classHeader . 'SummerPlans'];
            }
        }

        if ($requestType == "Add Food Fund" || $requestType == "Drop a Meal Plan")
        {
            $body .= "Current Meal Plan:           " . $mealPlan . "\n\n";
        }
        else if ($requestType == "Billing Inquiries" || $requestType == "Inquiry Only" || $requestType == "Other")
        {
            $mealPlan = $_POST[$this->classHeader . 'InquiryPlans'];
            $body .= "Inquiry related to:          " . $mealPlan . "\n\n";
        }
        else
        {
            $body .= "Desired Meal Plan:           " . $mealPlan ."\n\n";
        }

        if ($requestType == "Add Food Fund")
        {
            $body .= "Deposit Amount:              " . "$ " . $foodFundAmount ."\n\n";
        }

        if ($requestType == "Add Food Fund" || $requestType == "Drop a Meal Plan" || $requestType == "Billing Inquiries" || $requestType == "Inquiry Only" || $requestType == "Other")
        {
            $customFields[1134] = $mealPlan;
            $customFields[1135] = "None";
        }
        else
        {
            $customField[1134] = "None";
            $customField[1135] = $mealPlan;
        }
        $customFields[2501] = ($requestType == "Billing Inquiries" ? "Billing Issues" : $requestType);
        $customFields[1138] = $term;
        $customFields[1137] = $termYear;
        $customFields[1136] = $resName;
        $customFields[3019] = 'Web';

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
            hideField('<?php echo $this->classHeader;?>lookupMealPlanRow');
            hideRequiredFieldGroup("<?php echo $this->classHeader;?>requestedSemesterRow");
            hideRequiredField("<?php echo $this->classHeader;?>requestTypeRow");
            hideRequiredField("<?php echo $this->classHeader;?>ResidenceLocationRow");
            hideField('<?php echo $this->classHeader;?>gracePeriodMessageRow');
            hideRequiredField('<?php echo $this->classHeader;?>foodFundAmountRow');
            hideRequiredField('<?php echo $this->classHeader;?>SFPlansRow');
            hideRequiredField('<?php echo $this->classHeader;?>SFDormPlansRow');
            hideRequiredField('<?php echo $this->classHeader;?>WinterPlansRow');
            hideRequiredField('<?php echo $this->classHeader;?>SummerPlansRow');
            hideRequiredField('<?php echo $this->classHeader;?>InquiryPlansRow');
            hideField('<?php echo $this->classHeader;?>dropGracePeriodServiceFeeMessageRow');
            hideField('<?php echo $this->classHeader;?>dormGracePeriodChangeMessageRow');
            hideRequiredField('<?php echo $this->classHeader;?>otherRequestTypeRow');
            hideRequiredField("<?php echo $this->classHeader;?>foodFundAmountRow");
            hideField("<?php echo $this->classHeader;?>gracePeriodFYIMessageRow");
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
            let termYear = document.getElementById('<?php echo $this->classHeader;?>requestedYear').value;
            let termPrefix = '';
            let termValue = document.getElementById('<?php echo $this->classHeader;?>requestedTerm').value;

            let reqType = document.getElementById('<?php echo $this->classHeader;?>requestType').value;

            showField('<?php echo $this->classHeader;?>lookupMealPlanRow');
            showRequiredFieldGroup("<?php echo $this->classHeader;?>requestedSemesterRow");
            showRequiredField("<?php echo $this->classHeader;?>requestTypeRow");

            switch (reqType)
            {
                case "Add Food Fund":
                    showRequiredField("<?php echo $this->classHeader;?>foodFundAmountRow");
                break;

                case "Add a Meal Plan":
                case "Change a Meal Plan":
                case "Drop a Meal Plan":
                    showRequiredField("<?php echo $this->classHeader;?>ResidenceLocationRow");

                    let resValue = document.getElementById('<?php echo $this->classHeader;?>ResidenceLocation').value;
                    let resArr = resValue.split(",");
                    let resCode = resArr[0];
                    let resName = resArr[1];
                    let mealPlan = '';
                    let planIdx = 0;

                    if (termValue == 'Fall' || termValue == 'Spring')
                    {
                        if (resCode == 'C' || resCode == 'A')
                        {
                            termPrefix = "SF";
                            showRequiredField('<?php echo $this->classHeader;?>SFPlansRow');
                            mealPlan = document.getElementById('<?php echo $this->classHeader;?>SFPlans').value;
                            planIdx = document.getElementById('<?php echo $this->classHeader;?>SFPlans').selectedIndex;
                        }
                        else if (resCode == 'D')
                        {
                            termPrefix = "SFDorm";
                            showRequiredField('<?php echo $this->classHeader;?>SFDormPlansRow');
                            planIdx = document.getElementById('<?php echo $this->classHeader;?>SFDormPlans').selectedIndex;
                            mealPlan = document.getElementById('<?php echo $this->classHeader;?>SFDormPlans').value;
                        }
                    }
                    else
                    {
                        if (termValue == 'Winter')
                        {
                            termPrefix = "Winter";
                            showRequiredField('<?php echo $this->classHeader;?>WinterPlansRow');
                            planIdx = document.getElementById('<?php echo $this->classHeader;?>WinterPlans').selectedIndex;
                            mealPlan = document.getElementById('<?php echo $this->classHeader;?>WinterPlans').value;
                        }
                        else if (termValue == 'Summer')
                        {
                            termPrefix = "Summer";
                            showRequiredField('<?php echo $this->classHeader;?>SummerPlansRow');
                            planIdx = document.getElementById('<?php echo $this->classHeader;?>SummerPlans').selectedIndex;
                            mealPlan = document.getElementById('<?php echo $this->classHeader;?>SummerPlans').value;
                        }
                    }

                    if (reqType == 'Change a Meal Plan')
                    {
                        if (resCode == 'D') {
                            showField('<?php echo $this->classHeader;?>dormGracePeriodChangeMessageRow');
                        }
                    }
                    if (reqType == 'Drop a Meal Plan' && termPrefix)
                    {
                        document.getElementById(<?php echo $this->classHeader;?>termPrefix+'MealPlanCode').innerHTML = 'Current';
                    }

                    if (reqType == 'Drop a Meal Plan')
                    {  // && mealPlan!='Food Fund' /*&& planIdx>0) {
                        if (resCode == 'D' && mealPlan != 'Food Fund') {
                            showField('<?php echo $this->classHeader;?>gracePeriodMessageRow');
                        }
                        else
                        {
                            if (resCode == 'C' || resCode == 'A')
                            {
                                showField('<?php echo $this->classHeader;?>dropGracePeriodServiceFeeMessageRow');
                            }
                        }
                    }
                break;

                case "Other":
                    showRequiredField('<?php echo $this->classHeader;?>otherRequestTypeRow');
                case "Billing Inquiries":
                case "Inquiry Only":
                    showRequiredField("<?php echo $this->classHeader;?>InquiryPlansRow");
                break;

                default:
                break;
            }
            showField("<?php echo $this->classHeader;?>gracePeriodFYIMessageRow");
        }
        </script>
        <?php
    }

    public function html_outputFields()
    {
        ?>
        <div class="container" id="<?php echo $this->classHeader;?>Container">
            <div class="row mb-3" id="<?php echo $this->classHeader;?>lookupMealPlanRow">
                <div class="col-md-8 col-lg-6 offset-md-2">
                    <b><a href='https://campuscard-selfservice.umbc.edu/login.php' class="btn btn-info" target="_blank">Look up current meal plan</a></b>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>requestedSemesterRow">
                <div class="col-md-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>requestedSemester" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>requestedSemester">Semester:</label>
                </div>
                <div class="col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text">Semester</span>
                        <select id="<?php echo $this->classHeader;?>requestedTerm" name="<?php echo $this->classHeader;?>requestedSemester[]"  class="form-select" onchange='updateFormControls();' required>
                            <option value="" selected>Select the Semester</option>
                            <option value="Fall">Fall</option>
                            <option value="Winter">Winter</option>
                            <option value="Spring">Spring</option>
                            <option value="Summer">Summer</option>
                        </select>
                        <span class="input-group-text">Year</span>
                        <select id="<?php echo $this->classHeader;?>requestedYear" name="<?php echo $this->classHeader;?>requestedSemester[]" class="form-select" onchange='updateFormControls();' required>
                            <option value="" selected>Select the Year</option>
            <?php
            $currSysTime=time();
            $currYear = intval(date('Y', $currSysTime));
            $lastYear=$currYear+6;
            for ($yy=$currYear;$yy<=$lastYear; $yy++) {
                echo "<option value='$yy'>$yy</option>\n";
            }
            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>requestTypeRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>requestType" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>requestType">Request Type:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>requestType" name="<?php echo $this->classHeader;?>requestType" class="form-select" onchange='updateFormControls();' required>
                        <option value="" selected>Select a Request Type</option>
                        <option value="Add Food Fund">Add Food Fund</option>
                        <option value="Add a Meal Plan">Add a Meal Plan (You don't have one)</option>
                        <option value="Billing Inquiries">Billing Inquiries</option>
                        <option value="Change a Meal Plan">Change a Meal Plan</option>
                        <option value="Drop a Meal Plan">Drop a Meal Plan</option>
                        <option value="Inquiry Only">Inquiry Only</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id='<?php echo $this->classHeader;?>otherRequestTypeRow'>
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>otherRequestType" style='color:#f00;'>*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>otherRequestType">Other Request Type:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <input name='<?php echo $this->classHeader;?>otherRequestType' id='<?php echo $this->classHeader;?>otherRequestType' type="text" class="form-control" />
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>dormGracePeriodChangeMessageRow">
                <div class="col-md-8 col-lg-6 offset-md-2" style="color:#f00;font-size:12px;font-weight:bold;">
                If it is after the grace period (two weeks after the term has started), <br />dorm residents may not downgrade meal plans.
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>dropGracePeriodServiceFeeMessageRow">
                <div class="col-md-8 col-lg-6 offset-md-2" style="color:#f00;font-size:12px;font-weight:bold;">
                If it is after the grace period (two weeks after the term has started), <br />there will be a $35 service fee to downgrade or drop a meal plan.
                </div>
            </div>
            <div class="row mb-3" id="<?php echo $this->classHeader;?>gracePeriodMessageRow">
                <div class="col-md-8 col-lg-6 offset-md-2" style="color:#f00;font-size:12px;font-weight:bold;">
                If it is after the grace period (two weeks after the term has started), <br />you may NOT drop your meal plan.
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>ResidenceLocationRow">
                <div class="col-md-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>ResidenceLocation" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>ResidenceLocation">Residence:</label>
                </div>
                <div class="col-md-8 col-lg-6">
                    <select name="<?php echo $this->classHeader;?>ResidenceLocation" id="<?php echo $this->classHeader;?>ResidenceLocation" class="form-select" onchange='updateFormControls();' required>
                        <option value="" selected>Select a Residence</option>
                        <optgroup style="padding-left: 0em" label="Commuter">
                            <option value="C,Commuter">Commuter</option>
                        </optgroup>
                        <optgroup style="padding-left: 0em" label="Dorm">
                            <option value="D,Susquehanna">Susquehanna</option>
                            <option value="D,Chesapeake">Chesapeake</option>
                            <option value="D,Patapsco">Patapsco</option>
                            <option value="D,Erickson">Erickson</option>
                            <option value="D,Potomac">Potomac</option>
                            <option value="D,Harbor Hall">Harbor Hall</option>
                        </optgroup>
                        <optgroup style="padding-left: 0em" label="Apartment">
                            <option value="A,Hillside">Hillside</option>
                            <option value="A,Terrace">Terrace</option>
                            <option value="A,Walker">Walker</option>
                            <option value="A,Westhill">Westhill</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>SFPlansRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>SFPlans" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>SFPlans"><span id='<?php echo $this->classHeader;?>SFMealPlanCode'>Desired</span> Meal Plan:</label>		
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>SFPlans" name="<?php echo $this->classHeader;?>SFPlans" class="form-select" onchange='updateFormControls();'>
                        <option value="" selected>Select a Plan</option>
                        <option value="Flexible 5">Flexible 5</option>
                        <option value="Flexible 10">Flexible 10</option>
                        <option value="Flexible 14">Flexible 14</option>
                        <option value="Mega 50">Mega 50</option>
                        <option value="Mini 25">Mini 25</option>
                        <option id='sf1Savvy16' value="Savvy 16">Savvy 16</option>
                        <option id='sf1Super225' value="Super 225">Super 225</option>
                        <option id='sf1Terr12' value="Terrific 12">Terrific 12</option>
                        <option value="Ultimate">Ultimate</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>SFDormPlansRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>SFDormPlans" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>SFDormPlans"><span id='<?php echo $this->classHeader;?>SFDormMealPlanCode'>Desired</span> Meal Plan:</label>		
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>SFDormPlans" name="<?php echo $this->classHeader;?>SFDormPlans" class="form-select" onchange='updateFormControls();'>
                        <option value="" selected>Select a Plan</option>
                        <option value="Flexible 10">Flexible 10</option>
                        <option value="Flexible 14">Flexible 14</option>
                        <option id='dormSavvy16' value="Savvy 16">Savvy 16</option>
                        <option id='dormSuper225' value="Super 225">Super 225</option>
                        <option id='dormTerr12'  value="Terrific 12">Terrific 12</option>
                        <option value="Ultimate">Ultimate</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>SummerPlansRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>SummerPlans" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>SummerPlans"><span id='<?php echo $this->classHeader;?>SummerMealPlanCode'>Desired</span> Meal Plan:</label>		
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>SummerPlans" name="<?php echo $this->classHeader;?>SummerPlans" class="form-select" onchange='updateFormControls();'>
                        <option value="" selected>Select a Plan</option>
                        <option value="Summer Terrific 12">Summer Terrific 12</option>
                        <option value="Summer 14">Summer 14</option>
                        <option value="Summer Flex ($50)">Summer Flex ($50)</option>
                        <option value="Summer Flex ($100)">Summer Flex ($100)</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>WinterPlansRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>WinterPlans" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>WinterPlans"><span id='<?php echo $this->classHeader;?>WinterMealPlanCode'>Desired</span> Meal Plan:</label>		
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>WinterPlans" name="<?php echo $this->classHeader;?>WinterPlans" class="form-select" onchange='updateFormControls();'>
                        <option value="" selected>Select a Plan</option>
                        <option value="Winter Flex ($50)">Winter Flex ($50)</option>
                        <option value="Winter Mini 25">Winter Mini 25</option>
                        <option id='winterSavvy16' value="Winter Savvy 16">Winter Savvy 16</option>
                        <option id='winterTerr12' value="Winter Terrific 12">Winter Terrific 12</option>
                        <option value="Winter Ultimate">Winter Ultimate</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>InquiryPlansRow">
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>InquiryPlans" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>InquiryPlans">Inquiry related to:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <select id="<?php echo $this->classHeader;?>InquiryPlans" name="<?php echo $this->classHeader;?>InquiryPlans" class="form-select" onchange="updateFormControls();">
                        <option value="" selected>Select a Plan</option>
                        <optgroup label="Fall/Spring">
                            <option value="Flexible 5">Flexible 5</option>
                            <option value="Flexible 10">Flexible 10</option>
                            <option value="Flexible 14">Flexible 14</option>
                            <option value="Mega 50">Mega 50</option>
                            <option value="Mini 25">Mini 25</option>
                            <option id='sf1Savvy16' value="Savvy 16">Savvy 16</option>
                            <option id='sf1Super225' value="Super 225">Super 225</option>
                            <option id='sf1Terr12' value="Terrific 12">Terrific 12</option>
                            <option value="Ultimate">Ultimate</option>
                        </optgroup>
                        <optgroup label="Summer">
                            <option value="Summer Terrific 12">Summer Terrific 12</option>
                            <option value="Summer 14">Summer 14</option>
                            <option value="Summer Flex ($50)">Summer Flex ($50)</option>
                            <option value="Summer Flex ($100)">Summer Flex ($100)</option>
                        </optgroup>
                        <optgroup label="Winter">
                            <option value="Winter Flex ($50)">Winter Flex ($50)</option>
                            <option value="Winter Mini 25">Winter Mini 25</option>
                            <option id='winterSavvy16' value="Winter Savvy 16">Winter Savvy 16</option>
                            <option id='winterTerr12' value="Winter Terrific 12">Winter Terrific 12</option>
                            <option value="Winter Ultimate">Winter Ultimate</option>
                        </optgroup>
                        <optgroup label="Food Fund">
                            <option value="Food Fund">Food Fund</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="row mb-3" id='<?php echo $this->classHeader;?>foodFundAmountRow'>
                <div class="col-md-3 col-lg-2 col-form-label">
                    <span id="spn-<?php echo $this->classHeader;?>foodFundAmount" class="req-span">*</span>
                    <label class="form-label" for="<?php echo $this->classHeader;?>foodFundAmount">Deposit Amount:</label>
                </div>
                <div class="col-md-7 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input name='<?php echo $this->classHeader;?>foodFundAmount' id='<?php echo $this->classHeader;?>foodFundAmount' type="number" class="form-control" step="0.01" min="5.00" onchange='formatCurrency(this);' />
                    </div>
                    <small class="form-text text-muted" style="color:#f00;">Minimum deposit amount is $5.00</small>
                </div>
            </div>

            <div class="row mb-3" id="<?php echo $this->classHeader;?>gracePeriodFYIMessageRow">
                <div class="col-md-8 col-lg-6 offset-md-2" style="color:#f00;font-size:12px;font-weight:bold;">
                    After the conclusion of the Schedule Adjustment Period set forth in the Academic Calendar for the current semester, meal plans may no longer be reduced or removed.
                </div>
            </div>
        </div>
        <?php
    }
}