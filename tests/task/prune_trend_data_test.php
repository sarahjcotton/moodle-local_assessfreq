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

/**
 * Unit tests for prune_trend_data
 *
 * @package   local_assessfreq
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_assessfreq\task\prune_trend_data
 */
class prune_trend_data_test extends \advanced_testcase {
    /**
     * Test pruning old quiz trend data.
     */
    public function test_prune_quiz_trend_data(): void {
        global $DB;
        $this->resetAfterTest();

        $now = 1594788000;

        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $quiz1 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 0.5)),
            'timeclose' => ($now + (3600 * 0.5)),
            'timelimit' => 3600,
        ]);

        $trackingtask = new \assessfreqsource_quiz\task\quiz_tracking();
        // Generate data points for the quiz.
        $trackingtask->execute();
        $trackingtask->execute();
        $trackingtask->execute();
        $originaldatapoints = $DB->get_records('local_assessfreq_trend', ['assessid' => $quiz1->id, 'module' => 'quiz']);
        // Create a second quiz.
        $quiz2 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 0.5)),
            'timeclose' => ($now + (3600 * 0.5)),
            'timelimit' => 3600,
        ]);
        // Generate 5 additional data points for each quizzes.
        $trackingtask->execute();
        $trackingtask->execute();
        $trackingtask->execute();
        $trackingtask->execute();
        $trackingtask->execute();

        $this->assertEquals(8, $DB->count_records('local_assessfreq_trend', ['assessid' => $quiz1->id, 'module' => 'quiz']));
        $this->assertEquals(5, $DB->count_records('local_assessfreq_trend', ['assessid' => $quiz2->id, 'module' => 'quiz']));

        set_config('trendlimit', 5, 'assessfreqreport_activity_dashboard');
        $prunetask = new prune_trend_data();

        // The quiz with additional data points should be pruned down to the trend limit.
        $this->expectOutputString("Pruned 3 data points for quiz instance {$quiz1->id}" . PHP_EOL);
        $prunetask->execute();
        $this->assertEquals(5, $DB->count_records('local_assessfreq_trend', ['assessid' => $quiz1->id, 'module' => 'quiz']));
        $this->assertEquals(5, $DB->count_records('local_assessfreq_trend', ['assessid' => $quiz2->id, 'module' => 'quiz']));
        // The older data points should be removed.
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($originaldatapoints));
        $this->assertFalse($DB->record_exists_select('local_assessfreq_trend', "id {$insql}", $inparams));
    }
}
