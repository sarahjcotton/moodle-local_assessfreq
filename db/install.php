<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Installation for local_assessfreq.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\task\manager;
use local_assessfreq\task\history_process;

/**
 * Generate ad-hoc task on install.
 */
function xmldb_local_assessfreq_install() {
    if (!PHPUNIT_TEST) { // I hate this anti-pattern.
        // Create an adhoc task that will process all historical event data.
        $task = new history_process();
        manager::queue_adhoc_task($task, true);
    }

    // Set the default values as these are not picked up by the admin_apply_default_settings
    set_config('enabled', true, 'assessfreqsource_assign');
    set_config('enabled', true, 'assessfreqsource_choice');
    set_config('enabled', true, 'assessfreqsource_data');
    set_config('enabled', true, 'assessfreqsource_feedback');
    set_config('enabled', true, 'assessfreqsource_forum');
    set_config('enabled', true, 'assessfreqsource_lesson');
    set_config('enabled', true, 'assessfreqsource_quiz');
    set_config('enabled', true, 'assessfreqsource_scorm');
    set_config('enabled', true, 'assessfreqsource_workshop');

    set_config('enabled', true, 'assessfreqreport_activities_in_progress');
    set_config('enabled', true, 'assessfreqreport_activity_dashboard');
    set_config('enabled', true, 'assessfreqreport_heatmap');
    set_config('enabled', true, 'assessfreqreport_student_search');
    set_config('enabled', true, 'assessfreqreport_summary_graphs');
}
