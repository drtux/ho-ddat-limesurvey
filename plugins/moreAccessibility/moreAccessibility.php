<?php
/**
 * Add/fix some accessibility for LimeSurvey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2015-2016 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 1.4.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class moreAccessibility extends PluginBase
{
    protected $storage = 'DbStorage';

    static protected $name = 'moreAccessibility';
    static protected $description = 'Update HTML for question for better labelling, and other accessibility waiting for CSS Aria';

    protected $settings = array(
        'infoAlwaysActivate'=>array(
            'type' => 'info',
            'content' => '<div class="alert alert-info"><dl><dt>Some system can not be deactivated :</dt><dd> Use question text for labelling the single question type,</dd><dd>Fix checkbox with other label and radio with other label.</dd></dl></div>',
        ),
        'updateAsterisk' => array(
            'type' => 'select',
            'options'=>array(
                0=> 'No',
                1=> 'Yes'
            ),
            'default'=>0,
            'label' => 'Update asterisk part to show real sentence.'
        ),
        'addAnswersFieldSet' => array(
            'type' => 'select',
            'options'=>array(
                0=> 'No, use only aria',
                1=> 'Yes'
            ),
            'default'=>0,
            'label' => 'Add fieldset to answers art (list and array), attention : this can break your template.',
            'help' => 'Is set to no : Radio list and checkbox list use aria=group and labelled-by'
        ),
    );

    /**
    * Add function to be used in beforeQuestionRender event
    */
    public function init()
    {
        $this->subscribe('beforeQuestionRender','questiontextLabel');
        $this->subscribe('beforeQuestionRender','mandatoryString');
        $this->subscribe('beforeQuestionRender','questionanswersListGrouping');
        $this->subscribe('beforeQuestionRender','checkboxLabelOther');
        $this->subscribe('beforeQuestionRender','radioLabelOther');
        $this->subscribe('beforeQuestionRender','dropdownLabelOther');
    }

    /**
    * Use the question text as label for single question , use aria-labelledby for help and tips
    */
    public function questiontextLabel()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        // Label
        if(in_array($sType,array(
            "S","T","U",// Text question
            "N",// Numerical
            "I",// Language changer
            "!", // List dropdown
            )))
        {
            $this->registerCssJs();
            $sAnswerId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
            $aLabelledBy=$this->getDescribedBy($sAnswerId);
            Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
            Yii::import('archon810.SmartDOMDocument');
            $dom = new \archon810\SmartDOMDocument();
            $dom->loadHTML("<HTML><body>".$oEvent->get('answers')."</body></HTML>"); // Adding HTML/body : dropdwon with other have a bad script in 2.06

            foreach($dom->getElementsByTagName('label') as $label)
              $label->parentNode->removeChild($label);
            $input=$dom->getElementById($sAnswerId);
            if($input)
                $input->setAttribute("aria-labelledby",implode(" ",$aLabelledBy));
            else
                tracevar("{$sAnswerId} Is not found in HTML produced for answers");
            $newHtml = $dom->saveHTMLExact();
            $oEvent->set('answers',$newHtml);
        }
        // Date question type give format information, leave it ?
        // @todo : list radio with coment with dropdown enabled and list radio with dropdown too sometimes

    }


    public function questionanswersListGrouping()
    {
        if($this->get('addAnswersFieldSet'))
            $this->addAnswersFieldSet();
        else
        {
            $this->ariaGroupOnList();
            $this->ariaGroupOnArray();
        }
    }
    /**
    * Add fieldset to multiple question or multiple answers, move quetsion text + help + tip to label
    */
    public function addAnswersFieldSet()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "M","P","Q","K", // Multiple question : text/numeric multiple
            ";",":", // Array of input text/number
            "Y","G","5","L","O", // Single choice (radio)
            "F","H","A","B","E","C","1" // The arrays
            )))
        {
            $this->registerCssJs();
            // No legend .... need more HTML update : fieldset must include questiontext + answers.
            $sLegend=CHtml::tag("div",array("class"=>'question-moved'),$oEvent->get('text'));
            $oEvent->set('text','');
            $sLegend.=CHtml::tag("div",array("class"=>'help-moved'),$oEvent->get('help'));
            $oEvent->set('help','');
            $sLegend.=CHtml::tag("div",array("class"=>'man_message-moved'),$oEvent->get('man_message'));
            $oEvent->set('man_message','');
            $sLegend.=CHtml::tag("div",array("class"=>'valid_message-moved'),$oEvent->get('valid_message'));
            $oEvent->set('valid_message','');
            $sLegend.=CHtml::tag("div",array("class"=>'file_valid_message-moved'),$oEvent->get('file_valid_message'));
            $oEvent->set('file_valid_message','');
            $oEvent->set('answers',CHtml::tag(
                'fieldset',
                array('form'=>'limesurvey','class'=>'fixfieldset'),
                CHtml::tag('legend',array(),$sLegend).$oEvent->get('answers')
                ));
        }
    }

    /**
    * Use aria to group answers part : list question type
    */
    public function ariaGroupOnList()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "M","P","Q","K", // Multiple question : text/numeric multiple
            "Y","G","5","L","O", // Single choice (radio)
            )))
        {
            $this->registerCssJs();
            $aDescribedBy=$this->getDescribedBy();
            switch ($sType)
            {
              case "Y":
              case "G":
              case "5":
              case "L":
              case "O":
                $sRole='radiogroup';
                break;
              default:
                $sRole='group';
            }
            Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
            Yii::import('archon810.SmartDOMDocument');
            $dom = new \archon810\SmartDOMDocument();
            $dom->loadHTML("<HTML><body>".$oEvent->get('answers')."</body></HTML>");
            foreach ($dom->getElementsByTagName('ul') as $elList)
            {
                $elList->setAttribute('role',$sRole);
                $elList->setAttribute('aria-labelledby',implode(" ",$aDescribedBy));
            }
            $newHtml = $dom->saveHTMLExact();
            $oEvent->set('answers',$newHtml);
        }
    }

    /**
    * Use aria to group answers part : array question type
    * @todo
    */
    public function ariaGroupOnArray()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');

        if(in_array($sType,array(
            "F","A","B","E",";",":", // The arrays
            "1", // Need more option dualscale_header, dropdown etc ...
            )))
        {
            $aGlobalDescribedBy=$this->getDescribedBy();
            $this->registerCssJs();
            Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
            Yii::import('archon810.SmartDOMDocument');
            $dom = new \archon810\SmartDOMDocument();
            $dom->loadHTML("<HTML><body>".$oEvent->get('answers')."</body></HTML>");
            foreach ($dom->getElementsByTagName('table') as $elTable)
            {
                $elTable->setAttribute('role','group');
                $elTable->setAttribute('aria-labelledby',implode(" ",$aGlobalDescribedBy));
                // Fix update summary ? Add aria-hidden to head ?
            }
            switch ($sType)
            {
              case "F":
                $bUseDropdown=QuestionAttribute::model()->find("qid=:qid and attribute=:attribute",array(':qid'=>$oEvent->get('qid'),':attribute'=>'use_dropdown'));
                if($bUseDropdown && $bUseDropdown->value)
                {
                  $sLineRole=null;
                  break;
                }
              case "B":
              case "A":
              case "E":
                $sLineRole='radiogroup';
                break;
              case "1": // Double radio group : @todo fix-it : add dualscale_header(A|B)
                $bUseDropdown=QuestionAttribute::model()->find("qid=:qid and attribute=:attribute",array(':qid'=>$oEvent->get('qid'),':attribute'=>'use_dropdown'));
                if($bUseDropdown && $bUseDropdown->value)
                {
                  $sLineRole=null;
                  break;
                }
              case ";":
              case ":":

              default:
                $sLineRole='group';
            }
            if($sLineRole)
            {
              foreach ($dom->getElementsByTagName('tbody') as $elBody)
              {
                foreach ($elBody->getElementsByTagName('tr') as $elLine)
                {
                  $sLineId=$elLine->getAttribute('id');
                  $sDescribedLine="";
                  foreach ($elLine->getElementsByTagName('th') as $elListHead)
                  {
                    if($elListHead->getAttribute('class')=="answertext")
                    {
                      $elListHead->setAttribute('id',"maccess-line-description-{$sLineId}");
                    }
                    // ANd for other description part
                  }
                  $elLine->setAttribute('role',$sLineRole);
                  $elLine->setAttribute('aria-labelledby',"maccess-line-description-{$sLineId}");
                }
              }
            }

            $newHtml = $dom->saveHTMLExact();
            $oEvent->set('answers',$newHtml);
        }
        // H : array on column
    }
    /**
    * Update the mandatory * to a clean string, according to question type
    */
    public function mandatoryString()
    {
        if(!$this->get('updateAsterisk'))
            return;
        $oEvent=$this->getEvent();
        if($oEvent->get('man_class') && strpos($oEvent->get('man_class'),'mandatory') !== false)
        {
            // Get the string from LimeSurvey core.
            $sMandatoryText=gT('This question is mandatory')."."; // Arg
            switch($oEvent->get('type'))
            {
                case 'M':
                case 'P':
                    $sMandatoryText.=gT('Please check at least one item.');
                    break;
                case 'A':
                case 'B':
                case 'C':
                case 'Q':
                case 'K':
                case 'E':
                case 'F':
                case 'J':
                case 'H':
                case ';':
                case '1':
                    $sMandatoryText.=gT('Please complete all parts').'.';
                    break;
                case ':':
                    $oAttribute=QuestionAttribute::model()->find("qid=:qid and attribute=:attribute",array(":qid"=>$oEvent->get('qid'),':attribute'=>'multiflexible_checkbox'));
                    if($oAttribute && $oAttribute->value)
                        $sMandatoryText.=gT('Please check at least one box per row').'.';
                    else
                        $sMandatoryText.=gT('Please complete all parts').'.';
                    break;
                case 'R':
                    $sMandatoryText.=gT('Please rank all items').'.';
                    break;
                default:
                    break;
                case '*':
                case 'X':
                    $sMandatoryText="";
                    break;
            }
            $oEvent->set('mandatory',CHtml::tag('div',array('id'=>"maccess-mandatory-{$oEvent->get('qid')}",'class'=>"maccess-mandatory"),$sMandatoryText));
        }
    }

    /*
     * On checkbox list, when the "other" option is activated, hidden the checkbox and leave only label for other text
     */
    public function checkboxLabelOther()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "M", // Input Checkbox List
            )))
        {
          if (strpos( $oEvent->get('answers'), "othercbox") > 0) // Only do it if we have other : can be done with Question::model()->find
          {
              $sAnswerOtherTextId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}other";
              $sAnswerOtherCboxId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}othercbox";

              Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
              Yii::import('archon810.SmartDOMDocument');
              $dom = new \archon810\SmartDOMDocument();
              $dom->loadHTML("<HTML><body>".$oEvent->get('answers')."</body></HTML>");
              // Update the checkbox
              $cbox=$dom->getElementById($sAnswerOtherCboxId);
              if($cbox)
              {
                $cbox->setAttribute("aria-hidden","true");
                $cbox->setAttribute("tabindex","-1");
                $cbox->setAttribute("readonly","readonly");// disabled broken by survey-runtime
              }
              else
                tracevar("{$sAnswerOtherCboxId} Is not found in HTML produced for answers");
              // remove exiting script
              while (($r = $dom->getElementsByTagName("script")) && $r->length) {
                  $r->item(0)->parentNode->removeChild($r->item(0));
              }
              $newHtml = $dom->saveHTMLExact();
              $oEvent->set('answers',$newHtml);
              // Add own script
              $sMoreAccessibilityCheckboxLabelOtherScript="$(document).on('keyup focusout','#{$sAnswerOtherTextId}',function(){\n"
                      . "  if ($.trim($(this).val()).length>0) { $('#{$sAnswerOtherCboxId}').prop('checked',true); } else { $('#{$sAnswerOtherCboxId}').prop('checked',false); }\n"
                      ." $('#java{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}other').val($(this).val());LEMflagMandOther('{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}other',$('#{$sAnswerOtherCboxId}').is(':checked')); checkconditions($(this).val(), this.name, this.type);\n"
                      . "});\n"
                      . "$(document).on('click','#{$sAnswerOtherCboxId}',function(){\n"
                      . "  $('#{$sAnswerOtherTextId}').focus();\n"
                      . "})\n";
              App()->getClientScript()->registerScript('sMoreAccessibilityCheckboxLabelOtherScript',$sMoreAccessibilityCheckboxLabelOtherScript,CClientScript::POS_HEAD);
          }
        }
    }
    /*
    * Fix labelling on other in radio list : use aria-labelledby for text input
    * @link https://www.limesurvey.org/en/forum/plugins/100988-moreaccessibility#127710 Thanks to Alexandre Landry <forXcodeur@gmail.com>
    */
    public function radioLabelOther()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if(in_array($sType,array(
            "L", // Radio Button List
            )))
        {
          if (strpos( $oEvent->get('answers'), 'id="SOTH') > 0) // Only do it if we have other : can be done with Question::model()->find
          {
              $sAnswerOtherTextId="answer{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}othertext";
              $sAnswerOtherRadioId="SOTH{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
              Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
              Yii::import('archon810.SmartDOMDocument');
              $dom = new \archon810\SmartDOMDocument();
              $dom->loadHTML("<HTML><body>".$oEvent->get('answers')."</body></HTML>");
              $elOtherText=$dom->getElementById($sAnswerOtherTextId);
              if($elOtherText)
              {
                $elOtherText->setAttribute("aria-labelledby","label-{$sAnswerOtherRadioId}");
                $elOtherText->removeAttribute ('title');
                  foreach ($dom->getElementsByTagName('label') as $elLabel)
                  {
                      if($elLabel->getAttribute("for")==$sAnswerOtherRadioId)
                          $elLabel->setAttribute('id',"label-{$sAnswerOtherRadioId}");
                      if($elLabel->getAttribute("for")==$sAnswerOtherTextId)
                      {
                          $elLabel->parentNode->replaceChild($elOtherText, $elLabel);
                      }
                  }
              }
              else
                tracevar("{$elOtherText} Is not found in HTML produced for answers");
              $newHtml = $dom->saveHTMLExact();
              $oEvent->set('answers',$newHtml);
          }
        }
    }
    /*
    * Fix labelling on other in dropdown list : use aria-labelledby for text input
    */
    public function dropdownLabelOther()
    {
        $oEvent=$this->getEvent();
        $sType=$oEvent->get('type');
        if($sType=="!")
        {
          if (strpos( $oEvent->get('answers'), 'id="othertext') > 0) // Only do it if we have other : can be done with Question::model()->find
          {
              $sAnswerOtherTextId="othertext{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
              Yii::setPathOfAlias('archon810', dirname(__FILE__)."/vendor/archon810/smartdomdocument/src");
              Yii::import('archon810.SmartDOMDocument');
              $dom = new \archon810\SmartDOMDocument();
              $dom->loadHTML("<HTML><body>".$oEvent->get('answers')."</body></HTML>");
              $elOtherText=$dom->getElementById($sAnswerOtherTextId);
              if($elOtherText)
              {
                /* find the option and add it the label : is it a good way ? */
                foreach ($dom->getElementsByTagName('option') as $option)
                {
                  if($option->getAttribute('value') == "-oth-")
                  {
                    $elOtherOtion=$option;
                    $option->setAttribute('id',"label-{$sAnswerOtherTextId}");
                    $elOtherText->setAttribute('aria-labelledby',"label-{$sAnswerOtherTextId}");
                    $newHtml = $dom->saveHTMLExact();
                    $oEvent->set('answers',$newHtml);
                    break;
                  }
                }
              }


          }
        }
    }
    /**
    * Register needed css and js
    */
    private function registerCssJs()
    {
        $assetUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets');
        Yii::app()->clientScript->registerCssFile($assetUrl . '/css/moreaccessibility.css');
    }

    /**
    * get the initial describedby or labelledby and add the id to element
    * @var sInputId : inputid
    * @return array of id to use in describedby or labelledby
    */
    public function getDescribedBy($sInputId="")
    {
        $oEvent=$this->getEvent();
        $iQid=$oEvent->get('qid');
        $aDescribedBy=array();
        if($oEvent->get('text'))
        {
          $aDescribedBy[]="maccess-text-{$iQid}";
          if($sInputId)
            $oEvent->set('text',CHtml::tag("label",array('for'=>$sInputId,'id'=>"maccess-text-{$iQid}",'class'=>'maccess-labelid maccess-text'),$oEvent->get('text')));
          else
            $oEvent->set('text',CHtml::tag("div",array('id'=>"maccess-text-{$iQid}",'class'=>'maccess-labelid maccess-text'),$oEvent->get('text')));
        }
        if($this->get('updateAsterisk') && $oEvent->get('man_class'))
        {
            $aDescribedBy[]="maccess-mandatory-{$iQid}";
        }
        if($oEvent->get('questionhelp'))
        {
            $aDescribedBy[]="maccess-questionhelp-{$iQid}";
            $oEvent->set('questionhelp',
              CHtml::tag('div',
                array('id'=>"maccess-questionhelp-{$iQid}",'class'=>'maccess-labelid maccess-questionhelp'),
                $oEvent->get('questionhelp')
              )
            );
        }
        if($oEvent->get('help'))
        {
            $aDescribedBy[]="maccess-help-{$iQid}";
            $oEvent->set('help',
              CHtml::tag('div',
                array('id'=>"maccess-help-{$iQid}",'class'=>'maccess-labelid maccess-help'),
                $oEvent->get('help')
              )
            );
        }
        if(strip_tags($oEvent->get('valid_message'))!="")
        {
            $aDescribedBy[]="vmsg_{$iQid}";
        }
        else
        {
            $oEvent->set('valid_message','');
        }
        return $aDescribedBy;
    }
}
