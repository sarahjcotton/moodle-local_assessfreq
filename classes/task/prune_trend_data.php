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

namespace local_assessfreq\task;

use core\task\scheduled_task;

/**
 * Prune older trend data points that will not be used in reports.
 *
 * Based on the trend limit set in the activity dashboard report, find any data points older than that and delete them.
 *
 * @package   local_assessfreq
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prune_trend_data extends scheduled_task {
    #[\Override]
    public function get_name() {
        return get_string('task:prunetrenddata', 'local_assessfreq');
    }

    #[\Override]
    public function execute(): void {
        global $DB;
        $trendlimit = get_config('assessfreqreport_activity_dashboard', 'trendlimit');
        if (!$trendlimit) {
            return;
        }
        // Find assessments with data to be pruned.
        $assessments = $DB->get_recordset_sql(
            "SELECT COUNT('x') AS datapoints, assessid, module
               FROM {local_assessfreq_trend}
           GROUP BY assessid, module
             HAVING COUNT('x') > ?",
            [$trendlimit],
        );
        foreach ($assessments as $assessment) {
            // Find data points older than the limit, and delete them.
            $pruneids = $DB->get_records(
                'local_assessfreq_trend',
                ['module' => $assessment->module, 'assessid' => $assessment->assessid],
                'timecreated DESC, id DESC',
                fields: 'id',
                limitfrom: $trendlimit,
            );
            if (!empty($pruneids)) {
                $DB->delete_records_list('local_assessfreq_trend', 'id', array_keys($pruneids));
                mtrace("Pruned " . count($pruneids) . " data points for {$assessment->module} instance {$assessment->assessid}");
            }
        }
        $assessments->close();
    }
}
