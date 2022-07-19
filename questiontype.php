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
 * Question type class for the short answer similarity question type.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/shortanssimilarity/question.php');

/**
 * The short answer similarity question type.
 *
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_shortanssimilarity extends question_type {

    /**
     * Used to move files along with questions.
     * @param int $questionid With respect to each question id. Indexed starting from 0.
     * @param int $oldcontextid Contains old context id. Indexed starting from 0.
     * @param int $newcontextid Contains old newcontextid . Indexed starting from 0.
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_answers($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    /**
     * Used to delete files.
     * @param int $questionid With respect to each question id. Indexed starting from 0.
     * @param int $contextid Contains context id. Indexed starting from 0.
     * @return void
     */
    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    /**
     * Used to save questions.
     * @param object $question Contains the question.
     * @return void
     */
    public function save_question_options($question) {
        global $DB;

        $options = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $question->id));

        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->key_text = '';
            $options->item_language = 'en';
            $options->result = 0;
            $options->finished = 0;
            $options->manual_grading = 0;
            $options->id = $DB->insert_record('qtype_shortanssimilarity', $options);
        }

        $options->key_text = isset($question->key) ? $question->key : $question->key_text;
        $options->item_language = isset($question->language) ? $question->language : $question->item_language;
        $options->manual_grading = $question->manual_grading;

        $DB->update_record('qtype_shortanssimilarity', $options);

        $this->save_hints($question);
    }

    /**
     * Used to populates fields such as combined feedback.
     * Also make $DB calls to get data from other tables.
     * @param object $question Contains the question.
     * @return void
     */
    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    /**
     * Executed at runtime (e.g. in a quiz or preview).
     * @param question_definition $question Contains the question.
     * @param object $questiondata Contains the question data.
     * @return void
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->key_text = $questiondata->options->key_text;
        $question->item_language = $questiondata->options->item_language;
        $question->result = $questiondata->options->result;
        $question->finished = $questiondata->options->finished;
        $question->manual_grading = $questiondata->options->manual_grading;
    }

    /**
     * Used to delete questions.
     * @param int $questionid With respect to each question id. Indexed starting from 0.
     * @param int $contextid Contains old context id. Indexed starting from 0.
     * @return void
     */
    public function delete_questions($questionid, $contextid) {
        global $DB;

        $DB->delete_records('qtype_shortanssimilarity', array('questionid' => $question->id));
        parent::delete_questions($questionid, $contextid);
    }

    /**
     * If your question type has a table that extends the question table, and
     * you want the base class to automatically save, backup and restore the extra fields,
     * override this method to return an array where the first element is the table name,
     * and the subsequent entries are the column names (apart from id and questionid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    public function extra_question_fields() {
        return [
            'qtype_shortanssimilarity',
            'key_text',
            'item_language',
            'result',
            'finished',
            'manual_grading'
        ];
    }

    /**
     * Used to import data from xml.
     * Same as Moodle's, but with 'key_text' instead of 'answer' and
     * underscores removed from variable names.
     *
     * @param object $data The XML data.
     * @param object $question Contains the question.
     * @param qformat_xml $format Contains the format type.
     * @param string $extra Anything extra.
     * @return object
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {

        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $extraquestionfields = $this->extra_question_fields();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        foreach ($extraquestionfields as $field) {
            $qo->$field = $format->getpath($data, array('#', $field, 0, '#'), '');
        }

        // Run through the answers.
        $answers = $data['#']['key_text'];
        $acount = 0;
        $extraanswersfields = $this->extra_answer_fields();
        if (is_array($extraanswersfields)) {
            array_shift($extraanswersfields);
        }
        foreach ($answers as $answer) {
            $ans = $format->import_answer($answer);
            if (!$this->has_html_answers()) {
                $qo->answer[$acount] = $ans->answer['text'];
            } else {
                $qo->answer[$acount] = $ans->answer;
            }
            $qo->fraction[$acount] = $ans->fraction;
            $qo->feedback[$acount] = $ans->feedback;
            if (is_array($extraanswersfields)) {
                foreach ($extraanswersfields as $field) {
                    $qo->{$field}[$acount] =
                        $format->getpath($answer, array('#', $field, 0, '#'), '');
                }
            }
            ++$acount;
        }
        return $qo;
    }

    /**
     * Used to generate a random score.
     * @param object $questiondata Contains the question data.
     * @return int
     */
    public function get_random_guess_score($questiondata) {
        return 0;
    }

    /**
     * Used to get the key text for this question.
     * @param object $questiondata Contains the question data.
     * @return array
     */
    public function get_possible_responses($questiondata) {
        return array($questiondata->options->id => $questiondata->options->key_text);
    }
}
