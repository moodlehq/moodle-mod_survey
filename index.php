<?PHP // $Id$

    require("../../config.php");
    require("lib.php");

    require_variable($id);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "survey", "view all", "index.php?id=$course->id", "");

    if ($course->category) {
        $navigation = "<A HREF=\"../../course/view.php?id=$course->id\">$course->shortname</A> ->";
    }

    $strsurveys = get_string("modulenameplural", "survey");
    $strweek = get_string("week");
    $strtopic = get_string("topic");
    $strname = get_string("name");
    $strstatus = get_string("status");
    $strdone  = get_string("done", "survey");
    $strnotdone  = get_string("notdone", "survey");

    print_header("$course->shortname: $strsurveys", "$course->fullname", "$navigation $strsurveys", "");

    if (! $surveys = get_all_instances_in_course("survey", $course->id, "cw.section ASC")) {
        notice("There are no surveys.", "../../course/view.php?id=$course->id");
    }
    
    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname, $strstatus);
        $table->align = array ("CENTER", "LEFT", "LEFT");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname, $strstatus);
        $table->align = array ("CENTER", "LEFT", "LEFT");
    } else {
        $table->head  = array ($strname, $strstatus);
        $table->align = array ("LEFT", "LEFT");
    }

    foreach ($surveys as $survey) {
        if (survey_already_done($survey->id, $USER->id)) {
            $ss = $strdone;
        } else {
            $ss = $strnotdone;
        }
        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ("$survey->section", 
                                    "<A HREF=\"view.php?id=$survey->coursemodule\">$survey->name</A>",
                                    "<A HREF=\"view.php?id=$survey->coursemodule\">$ss</A>");
        } else {
            $table->data[] = array ("<A HREF=\"view.php?id=$survey->coursemodule\">$survey->name</A>",
                                    "<A HREF=\"view.php?id=$survey->coursemodule\">$ss</A>");
        }
    }

    echo "<BR>";
    print_table($table);
    print_footer($course);

?>
