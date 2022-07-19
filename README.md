# Short Answer Similarity Question Type

This plugin is a moodle question type that can be added to any moodle quiz.

The plugin extracts the text from the answer provided by teacher and from the student's response. Once the two strings are extracted, the similarity between the two multi-sentences is calculated by the VIP Research Group's multi sentence similarity calculator web service.

## Installation

### Installation Using Git 

To install using git for the latest version (the master branch), type this command in the
root of your Moodle install:

    git clone https://github.com/VIP-Research-Group/moodle-qtype_shortanssimilarity.git question/type/shortanssimilarity

### Installation From Downloaded zip file

Alternatively, download the zip (can be found at the follwing link after you click the green code button): https://github.com/VIP-Research-Group/moodle-qtype_shortanssimilarity

unzip it into the question/type folder, and then rename the new folder to shortanssimilairty.

## How To Use

If properly installed, when adding questions to a quiz in Moodle, the option to add a "Short Answer Similarity" question should be available. Fill in the required form data and save the question

### Manual Marking on or off?

This section explains the option to turn manual marking on or off in the question creation page.

- Maunal Marking: Requires cron. If the manual marking option is set to yes, this question will default to maunal marking and the question will be marked in the background. Once the question is finished marking, the question text will be updated with the result (This will be visible to both teachers and students but will not actually mark the question). From there the teacher can review the question and update the mark. Use IF the provided answer (Key Text) is longer than 2 sentences.

- Automatic Marking: Does not require cron. If the manual marking option to no, then will use automatic marking. Although, load times will be drastically longer (can last 10 minutes per question, if responses are compilcated). Therefore we recommend this option be set to no, IF the answer (Key Text) is less then or equal to 2 sentences.';
 
 ## Terms of Use
 This plugin uses VIP Research Group's multi-sentence similarity calculation web service (https://ws-nlp.vipresearch.ca/). The VIP Research Group is a research group led by Prof. Maiga Chang (https://www.athabascau.ca/science-and-technology/our-people/maiga-chang.html) at School of Computing and Information Systems, Athabasca University. The "multi-sentence similarity calculation web service" is one of the research group's works. The research group does have follow-up research plan to improve it and further use it in other research projects.
 
 Almost all of Prof. Chang's works are open access (or open source). The web service (https://ws-nlp.vipresearch.ca/) is now open access and there is no plan to make it open source. The web service is open access and running on a self-sponsored server, as all of other research projects (see http://maiga.athabascau.ca/#advanced) they will be always online, improving, and accessible as long as the cost can be affordable and covered by Prof. Chang.
 
 Of course if in any case just like the access volume of the web service becoming high or any business/commercial takes advantage of using it to make money, then the term of using the web service may look for changes; for examples, donations, personal/academic/business license and subscription modes, etc. However, it is really too early to say that.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see http://www.gnu.org/licenses/.
