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
 * Defines the editing form for the similarity calculator question type.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * short answer similarity question editing form definition.
 *
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_shortanssimilarity_edit_form extends question_edit_form {

    /**
     * Fetch a list of supported languages.
     *
     * @return an object that holds the supported languages
     */
    public function get_languages() {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $url = "https://ws-nlp.vipresearch.ca/bridge/language_list.php";
        $json = download_file_content($url);
        $json = json_decode($json);

        return $json;
    }

    /**
     * Defining the whole form.
     * @param stdClass $mform - Reponsible for collecting the form components.
     * @return void
     */
    protected function definition_inner($mform) {
        global $DB;

        // If this question is being edited, then show the old answer and language.
        $id = optional_param('id', 0, PARAM_INT);
        $oldkey = '';
        $oldlang = 'en';
        $maxbpm = 0;
        $ngrampos = 0;
        $canonical = 0;
        if ($id) {
            $record = $DB->get_record('qtype_shortanssimilarity', ['questionid' => $id]);
            $oldkey = $record->key_text;
            $oldlang = $record->item_language;
            $maxbpm = $record->maxbpm;
            $ngrampos = $record->ngrampos;
            $canonical = $record->canonical;
        }

        // Add fields specific to this question type.
        $text = get_string('keytext', 'qtype_shortanssimilarity');
        $mform->addElement('textarea', 'key', $text, 'wrap="virtual" rows="10" cols="100"');
        $mform->addRule('key', get_string('error'), 'required' , '', 'client');
        $mform->addHelpButton('key', 'key_text', 'qtype_shortanssimilarity');
        $mform->setType('key', PARAM_NOTAGS);
        $mform->setDefault('key', $oldkey);

        $languages = $this->get_languages();
        $mform->addElement('select', 'language', get_string('language', 'qtype_shortanssimilarity'), (array) $languages);
        $mform->setType('language', PARAM_NOTAGS);
        $mform->setDefault('language', $oldlang);

        $options = array('yes' => get_string('yes'), 'no' => get_string('no'));

        // Yes/No select option for max BPM.
        $mform->addElement('select', 'maxbpm', get_string('maxbpm', 'qtype_shortanssimilarity'), $options);
        $mform->getElement('maxbpm')->setSelected($maxbpm);

        // Yes/No select option for Ngram-POS.
        $mform->addElement('select', 'ngrampos', get_string('ngrampos', 'qtype_shortanssimilarity'), $options);
        $mform->getElement('ngrampos')->setSelected($ngrampos);

        // Yes/No select option for canonical.
        $mform->addElement('select', 'canonical', get_string('canonical', 'qtype_shortanssimilarity'), $options);
        $mform->getElement('canonical')->setSelected($canonical);

        $text = get_string('manualmarking', 'qtype_shortanssimilarity');
        $mform->addElement('select', 'manual_grading', $text, array(true => 'yes', false => 'no'));
        $mform->addRule('manual_grading', get_string('error'), 'required');
        $mform->addHelpButton('manual_grading', 'manual_marking', 'qtype_shortanssimilarity');
        $mform->setType('manual_grading', PARAM_NOTAGS);

        // To add combined feedback (correct, partial and incorrect).
        $this->add_combined_feedback_fields(true);
        // Adds hinting features.
        $this->add_interactive_settings(true, true);
    }

    /**
     * Called to preprocess the data.
     * @param object $question Contains the question data.
     * @return object $question
     */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->key_text = $question->options->key_text;
        $question->item_language = $question->options->item_language;
        $question->result = 0;
        $question->finished = 0;
        $question->manual_grading = $question->options->manual_grading;

        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question);

        return $question;
    }

    /**
     * Returns the question type name.
     * @return string
     */
    public function qtype() {
        return 'shortanssimilarity';
    }
}
