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
 * Heatmap renderer.
 *
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_heatmap\output;

use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use local_assessfreq\frequency;
use plugin_renderer_base;

/**
 * Heatmap renderer.
 *
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * @var int
     */
    private int $preferenceyear;

    /**
     * @var mixed|string|null
     */
    private mixed $preferencemodules;

    /**
     * @var string
     */
    private string $preferencemetric;

    /**
     * @var int
     */
    private int $heatrangemax = 0;

    /**
     * @var int
     */
    private int $heatrangemin = 0;

    /**
     * @var int[]
     */
    private array $heatrangescales = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

    /**
     * Generate the HTML for the report.
     *
     * @return bool|string
     */
    public function render_report(): bool|string {

        if ($this->page->course->id !== SITEID && !get_config('assessfreqreport_heatmap', 'courselevelyearfilter')) {
            $this->preferenceyear = date('Y', $this->page->course->startdate);
        } else {
            $this->preferenceyear = get_user_preferences('assessfreqreport_heatmap_year_preference', date('Y'));
        }
        $this->preferencemodules = json_decode(
            get_user_preferences('assessfreqreport_heatmap_modules_preference', '["all"]'),
            true
        );
        $this->preferencemetric = get_user_preferences('assessfreqreport_heatmap_metric_preference', 'assess');

        $events = $this->get_events();

        $originalyear = $this->preferenceyear;
        $orderedmonths = local_assessfreq_get_months_ordered();

        $months = $this->get_calendar($this->preferenceyear, $orderedmonths, $events);

        $scalestable = new html_table();
        $scalestable->attributes['class'] = 'scales-table';
        $scalecells = [];
        foreach ($this->heatrangescales as $heatrangescale => $heatrangecount) {
            if ($heatrangecount) {
                $cell = new html_table_cell("$heatrangecount+");
                $cell->attributes['class'] = "scales-cell heat-$heatrangescale";
                $scalecells[] = $cell;
            }
        }
        $scalestable->data = [new html_table_row($scalecells)];

        $modules = local_assessfreq_get_modules($this->preferencemodules);

        $selectedmodules = [];
        foreach ($modules as $module) {
            if (isset($module['module']['active'])) {
                $selectedmodules[] = $module['module']['name'];
            }
        }

        $yearfilter = true;
        if ($this->page->course->id != SITEID) {
            $yearfilter = get_config('assessfreqreport_heatmap', 'courselevelyearfilter');
        }

        return $this->render_from_template(
            'assessfreqreport_heatmap/heatmap',
            [
                'filters' => [
                    'years' => local_assessfreq_get_years($this->preferenceyear),
                    'modules' => $modules,
                    'metrics' => [$this->preferencemetric => ['active' => true]],
                    'selected_modules' => implode(', ', $selectedmodules),
                    'selected_metric' => get_string("filter:metric:$this->preferencemetric", 'assessfreqreport_heatmap'),
                ],
                'downloadmetric' => $this->preferencemetric,
                'sesskey' => sesskey(),
                'yearfilter' => $yearfilter,
                'courseid' => $this->page->course->id,
                'months' => $months,
                'scales' => html_writer::table($scalestable),
                'year' => $originalyear,
                'month' => array_shift($orderedmonths),
            ]
        );
    }

    /**
     * Get the calendar of events.
     *
     * @param int $preferenceyear The year to get data for.
     * @param array $orderedmonths The ordered list of months.
     * @param array $events The events.
     * @return array
     */
    private function get_calendar(int $preferenceyear, array $orderedmonths, array $events): array {
        $months = [];

        foreach ($orderedmonths as $monthnumber => $monthname) {
            $monthtable = new html_table();
            $monthtable->head = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $monthtable->data = [];
            $monthtable->attributes['class'] = 'month-table';

            // Number of days in the month.
            $monthdays = date('t', mktime(0, 0, 0, date($monthnumber), 1, $preferenceyear));

            // Get first day of the month.
            $j = date('w', mktime(0, 0, 0, date($monthnumber), 1, $preferenceyear));

            $week = new html_table_row();
            for ($i = 1; $i <= $j; $i++) {
                $cell = new html_table_cell("&nbsp;");
                $cell->attributes = ['class' => "empty-cell"];
                $week->cells[] = $cell;
            }

            for ($i = 1; $i <= $monthdays; $i++) {
                // If we're at the end of a week, start a new row.
                if ($j % 7 == 0) {
                    $monthtable->data[] = $week;
                    $week = new html_table_row();
                }
                if (isset($events[$preferenceyear][$monthnumber][$i])) {
                    $cell = new html_table_cell($i);
                    $cell->attributes = [
                        'class' => "show-dialog has-events heat-" . $events[$preferenceyear][$monthnumber][$i]['heat'],
                        'data-target' => "$preferenceyear-$monthnumber-$i",
                    ];
                    $week->cells[] = $cell;
                } else {
                    $week->cells[] = new html_table_cell($i);
                }
                $j++;
            }
            $monthtable->data[] = $week;

            $months[] = ['month' => "$monthname - $preferenceyear", 'table' => html_writer::table($monthtable)];

            // If the start month isn't Jan then we need to increase the year for the months after Dec.
            if ($monthnumber == 12) {
                $preferenceyear++;
            }
        }

        return $months;
    }


    /**
     * Get all of the events and heat for each.
     *
     * @return array
     */
    private function get_events(): array {
        $frequency = new frequency();

        $orderedmonths = local_assessfreq_get_months_ordered();
        $startmonth = array_key_first($orderedmonths);

        $eventlist = $frequency->get_frequency_array(
            $this->preferenceyear,
            $startmonth,
            $this->preferencemetric,
            $this->preferencemodules
        );

        foreach ($eventlist as $year) {
            foreach ($year as $month) {
                foreach ($month as $day) {
                    $this->heatrangemax = max($this->heatrangemax, $day['number']);
                    $this->heatrangemin = min($this->heatrangemax, $day['number']);
                }
            }
        }

        foreach ($eventlist as &$year) {
            foreach ($year as &$month) {
                foreach ($month as &$day) {
                    $heat = $this->get_heat($day['number']);
                    $day['heat'] = $heat;
                    if (!$this->heatrangescales[$heat]) {
                        $this->heatrangescales[$heat] = $day['number'];
                    }
                    $this->heatrangescales[$heat] = min($day['number'], $this->heatrangescales[$heat]);
                }
            }
        }
        return $eventlist;
    }

    /**
     * Calculate the heat value based on the ranges available.
     *
     * @param int $count The number of events in a given day.
     * @return int
     */
    private function get_heat(int $count): int {
        $scalemin = 1;

        if ($count == $this->heatrangemin) {
            return $scalemin;
        }

        $scalerange = 5;  // 0 - 5  steps.
        $localrange = $this->heatrangemax - $this->heatrangemin;
        if ($localrange <= 0) {
            return 1;
        }
        $localpercent = ($count - $this->heatrangemin) / $localrange;
        $heat = round(($localpercent * $scalerange) + 1);

        // Clamp values.
        if ($heat < 1) {
            $heat = 1;
        } else if ($heat > 6) {
            $heat = 6;
        }

        return $heat;
    }
}
