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
 * Short Answer Similarity question renderer class.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generates the output for short answer similarity questions.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_shortanssimilarity_renderer extends qtype_renderer {

    /**
     * Responsible for the formulation and control.
     * @param question_attempt $qa The question attempt.
     * @param question_display_options $options The question display options.
     * @return string
     */
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        global $COURSE;

        $question = $qa->get_question();
        $responseoutput = $question->get_format_renderer($this->page);

        $step = $qa->get_last_step_with_qt_var('answer');

        if (!$step->has_qt_var('answer') && empty($options->readonly)) {
            // Question has never been answered, fill it with response template.
            $step = new question_attempt_step(array('answer' => ''));
        }

        if (empty($options->readonly)) {
            $answer = $responseoutput->response_area_input('answer', $qa,
                    $step, 15, $options->context);

        } else {
            $answer = '';

            if (!$question->is_completed_marking($step->get_qt_var('answer'), $step->get_user_id())) {
                $string = get_string('is_completed_false', 'qtype_shortanssimilarity');
            } else {
                $score = $question->get_grade($step->get_qt_var('answer'), $step->get_user_id());
                $string = get_string('is_completed_true', 'qtype_shortanssimilarity', $score);

                if ($question->using_chron()) {
                    $string .= ' ' . get_string('update_mark', 'qtype_shortanssimilarity');
                }
            }

            $answer = html_writer::nonempty_tag('p', $string);
            $answer .= "</br>";
            $answer .= $responseoutput->response_area_read_only('answer', $qa,
                    $step, 15, $options->context);
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $answer, array('class' => 'answer'));
        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Responsible for the providing feedback.
     * @param question_attempt $qa The question attempt.
     * @return string
     */
    public function specific_feedback(question_attempt $qa) {
        $question = $qa->get_question();
        return '';
    }

    /**
     * Responsible for the presenting correct response.
     * @param question_attempt $qa The question attempt.
     * @return string
     */
    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        return '';
    }
}
