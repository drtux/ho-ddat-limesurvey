# More accessibility for LimeSurvey #

Some more accessibility for LimeSurvey, actually:

- Replace the label for single question choice by label to the question
- Fix the other label in single choice (radio) and multiple choice
- Add roles and aria-labelledby for list of answers (radio and checkbox)
- Add roles and aria-labelledby for arrays of answers (radio, text and checkbox)
- Optionnal: Use a real string for the asterisk in mandatory question type
- Optionnal: Add fieldset for list of answers and subquestion (deactivate role and aria-labelledby for list and array).

This plugin was not tested with 2.50 version actually

## Installation

### Via GIT
- Go to your LimeSurvey Directory (version up to 2.05)
- Clone with Submodule in plugins/moreAccessibility directory : `git clone --recursive https://git.framasoft.org/SondagePro-LimeSurvey-plugin/moreAccessibility.git moreAccessibility`

### Via ZIP dowload
- Download <http://extensions.sondages.pro/IMG/auto/moreAccessibility.zip> or <http://extensions.sondages.pro/IMG/auto/moreAccessibility.tar>
- Extract : `unzip moreAccessibility.zip`
- Move the directory to  plugins/ directory inside LimeSUrvey

## Home page & Copyright
- HomePage <http://extensions.sondages.pro/>
- Demonstration <http://accessible.sondages.pro> (WIP :can be broken)
- Copyright © 2015-2016 Denis Chenu <http://sondages.pro>
- Licence : GNU General Public License <https://www.gnu.org/licenses/gpl-3.0.html>
- This plugin use SmartDOMDocument © 2015 Artem Russakovskii <http://beerpla.net>
- Icon use:
  - Person <https://thenounproject.com/icon/13025/> by [WebPlatform.org](https://thenounproject.com/WebPlatform/)
  - Checkboxes <https://thenounproject.com/icon/200545/> by [Alex Tai](https://thenounproject.com/skovalsky/)
  - Login Form <https://thenounproject.com/icon/223178/> by [icon 54](https://thenounproject.com/icon54app/)
