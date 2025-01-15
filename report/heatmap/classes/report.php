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
 * Main report class.
 *
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_heatmap;

use local_assessfreq\report_base;

/**
 * Main report class.
 *
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report extends report_base {
    /**
     * Weight is used to define the sort order.
     */
    const WEIGHT = 10;

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return get_string("tab:name", "assessfreqreport_heatmap");
    }

    /**
     * {@inheritDoc}
     */
    public function get_tab_weight(): int {
        return self::WEIGHT;
    }

    /**
     * {@inheritDoc}
     */
    public function get_tablink(): string {
        return 'heatmap';
    }

    /**
     * {@inheritDoc}
     */
    public function has_access(): bool {
        global $PAGE;

        return has_capability('assessfreqreport/heatmap:view', $PAGE->context);
    }

    /**
     * {@inheritDoc}
     */
    public function get_contents(): string {
        global $PAGE;

        $renderer = $PAGE->get_renderer("assessfreqreport_heatmap");

        return $renderer->render_report();
    }

    /**
     * {@inheritDoc}
     */
    protected function add_required_js(): void {
        global $PAGE;

        $PAGE->requires->js_call_amd('assessfreqreport_heatmap/heatmap', 'init', [$PAGE->course->id]);
    }

    /**
     * {@inheritDoc}
     */
    protected function add_required_css(): void {
        global $PAGE;
        // The CSS for the heatmap is based on plugin config. As such this needs to be in-line.
        $PAGE->requires->css('/local/assessfreq/report/heatmap/dynamic-styles.php');
    }
}
