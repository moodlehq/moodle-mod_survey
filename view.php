<?php // $Id$

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);    // Course Module ID

    if (! $cm = get_coursemodule_from_id('survey', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf');
    }

    require_login($course->id, false, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    require_capability('mod/survey:participate', $context);

    if (! $survey = $DB->get_record("survey", array("id"=>$cm->instance))) {
        print_error('invalidsurveyid', 'survey');
    }
    $trimmedintro = trim($survey->intro);
    if (empty($trimmedintro)) {
        $tempo = $DB->get_field("survey", "intro", array("id"=>$survey->template));
        $survey->intro = get_string($tempo, "survey");
    }

    if (! $template = $DB->get_record("survey", array("id"=>$survey->template))) {
        print_error('invalidtmptid', 'survey');
    }

    $showscales = ($template->name != 'ciqname');

    $strsurvey = get_string("modulename", "survey");
    $navigation = build_navigation('', $cm);
    print_header_simple(format_string($survey->name), "", $navigation, "", "", true,
                  update_module_button($cm->id, $course->id, $strsurvey), navmenu($course, $cm));

/// Check to see if groups are being used in this survey
    if ($groupmode = groups_get_activity_groupmode($cm)) {   // Groups are being used
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }
    $groupingid = $cm->groupingid;
    
    if (has_capability('mod/survey:readresponses', $context) or ($groupmode == VISIBLEGROUPS)) {    
        $currentgroup = 0;
    }
    
    if (has_capability('mod/survey:readresponses', $context)) {
        $numusers = survey_count_responses($survey->id, $currentgroup, $groupingid);
        echo "<div class=\"reportlink\"><a href=\"report.php?id=$cm->id\">".
              get_string("viewsurveyresponses", "survey", $numusers)."</a></div>";
    } else if (!$cm->visible) {
        notice(get_string("activityiscurrentlyhidden"));
    }

    if (isguest()) {
        notify(get_string("guestsnotallowed", "survey"));
    }


//  Check the survey hasn't already been filled out.

    if (survey_already_done($survey->id, $USER->id)) {

        add_to_log($course->id, "survey", "view graph", "view.php?id=$cm->id", $survey->id, $cm->id);
        $numusers = survey_count_responses($survey->id, $currentgroup, $groupingid);

        if ($showscales) {
            print_heading(get_string("surveycompleted", "survey"));
            print_heading(get_string("peoplecompleted", "survey", $numusers));
            echo '<div class="resultgraph">';
            survey_print_graph("id=$cm->id&amp;sid=$USER->id&amp;group=$currentgroup&amp;type=student.png");
            echo '</div>';

        } else {

            print_box(format_module_intro('survey', $survey, $cm->id), 'generalbox', 'intro');
            $spacer = new html_image();
            $spacer->height = 30;
            echo $OUTPUT->spacer(clone($spacer)) . '<br />';

            $questions = $DB->get_records_list("survey_questions", "id", explode(',', $survey->questions));
            $questionorder = explode(",", $survey->questions);
            foreach ($questionorder as $key => $val) {
                $question = $questions[$val];
                if ($question->type == 0 or $question->type == 1) {
                    if ($answer = survey_get_user_answer($survey->id, $question->id, $USER->id)) {
                        $table = NULL;
                        $table->head = array(get_string($question->text, "survey"));
                        $table->align = array ("left");
                        $table->data[] = array(s($answer->answer1));//no html here, just plain text
                        print_table($table);
                        echo $OUTPUT->spacer(clone($spacer)) . '<br />';
                    }
                }
            }
        }

        print_footer($course);
        exit;
    }

//  Start the survey form
    add_to_log($course->id, "survey", "view form", "view.php?id=$cm->id", $survey->id, $cm->id);

    echo "<form method=\"post\" action=\"save.php\" id=\"surveyform\">";
    echo '<div>';
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";

    print_simple_box(format_module_intro('survey', $survey, $cm->id), 'center', '70%', '', 5, 'generalbox', 'intro');

// Get all the major questions and their proper order
    if (! $questions = $DB->get_records_list("survey_questions", "id", explode(',', $survey->questions))) {
        print_error('cannotfindquestion', 'survey');
    }
    $questionorder = explode( ",", $survey->questions);

// Cycle through all the questions in order and print them

    $qnum = 0;
    foreach ($questionorder as $key => $val) {
        $question = $questions["$val"];
        $question->id = $val;

        if ($question->type >= 0) {

            if ($question->text) {
                $question->text = get_string($question->text, "survey");
            }

            if ($question->shorttext) {
                $question->shorttext = get_string($question->shorttext, "survey");
            }

            if ($question->intro) {
                $question->intro = get_string($question->intro, "survey");
            }

            if ($question->options) {
                $question->options = get_string($question->options, "survey");
            }

            if ($question->multi) {
                survey_print_multi($question);
            } else {
                survey_print_single($question);
            }
        }
    }

    if (isguest()) {
        echo '</div>';  
        echo "</form>";
        print_footer($course);
        exit;
    }

    $checkarray = Array('questions'=>Array());
    if (!empty($checklist)) {
       foreach ($checklist as $question => $default) {
           $checkarray['questions'][] = Array('question'=>$question, 'default'=>$default);
       }
    }
    $PAGE->requires->js('mod/survey/survey.js');
    $PAGE->requires->data_for_js('surveycheck', $checkarray);
    $PAGE->requires->string_for_js('questionsnotanswered', 'survey');
    $PAGE->requires->js_function_call('survey_attach_onsubmit');

    echo '<br />';
    echo '<input type="submit" value="'.get_string("clicktocontinue", "survey").'" />';
    echo '</div>';
    echo "</form>";

    print_footer($course);

?>
