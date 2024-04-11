<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace customfield_training;

use core_customfield_test_instance_form;

/**
 * Functional test for customfield_training
 *
 * @group      openlms
 * @package    customfield_training
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_test extends \advanced_testcase {

    /**
     * Tests set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test for initialising field and data controllers
     */
    public function test_initialise() {
        /** @var \core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $this->setUser($this->getDataGenerator()->create_user());

        $cfcat = $generator->create_category();
        $cfield1 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield1', 'type' => 'training']);
        $course1 = $this->getDataGenerator()->create_course();
        $cfdata1 = $generator->add_instance_data($cfield1, $course1->id,1);

        $f = \core_customfield\field_controller::create($cfield1->get('id'));
        $this->assertTrue($f instanceof field_controller);

        $f = \core_customfield\field_controller::create(0, (object)['type' => 'training'], $cfcat);
        $this->assertTrue($f instanceof field_controller);

        $d = \core_customfield\data_controller::create($cfdata1->get('id'));
        $this->assertTrue($d instanceof data_controller);

        $d = \core_customfield\data_controller::create(0, null, $cfield1);
        $this->assertTrue($d instanceof data_controller);
    }

    /**
     * Test for configuration form functions
     *
     * Create a configuration form and submit it with the same values as in the field
     */
    public function test_config_form() {
        $this->setAdminUser();

        /** @var \core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $cfcat = $generator->create_category();
        $cfield1 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield1', 'type' => 'training']);
        $submitdata = (array)$cfield1->to_record();
        $submitdata['configdata'] = $cfield1->get('configdata');

        $submitdata = \core_customfield\field_config_form::mock_ajax_submit($submitdata);
        $form = new \core_customfield\field_config_form(null, null, 'post', '', null, true,
            $submitdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $form->process_dynamic_submission();
    }

    /**
     * Test for instance form functions
     */
    public function test_instance_form() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/customfield/tests/fixtures/test_instance_form.php');

        /** @var \core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $this->setAdminUser();
        $cfcat = $generator->create_category();
        $handler = $cfcat->get_handler();
        $course1 = $this->getDataGenerator()->create_course();
        $cf1 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield1', 'type' => 'training']);
        $cf2 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield2', 'type' => 'training',
                'configdata' => ['required' => true]]);
        // First try to submit without required field.
        $submitdata = (array)$course1;
        $submitdata['customfield_myfield1'] = '';
        core_customfield_test_instance_form::mock_submit($submitdata, []);
        $form = new core_customfield_test_instance_form('POST',
            ['handler' => $handler, 'instance' => $course1]);
        $this->assertFalse($form->is_validated());

        // Should pass now.
        $submitdata['customfield_myfield2'] = '20';
        core_customfield_test_instance_form::mock_submit($submitdata, []);
        $form = new core_customfield_test_instance_form('POST',
            ['handler' => $handler, 'instance' => $course1]);
        $this->assertTrue($form->is_validated());
        $data = $form->get_data();
        $this->assertSame('', $data->customfield_myfield1);
        $this->assertSame('20', $data->customfield_myfield2);
        $handler->instance_form_save($data);

        $field1 = $DB->get_record('customfield_data', ['fieldid' => $cf1->get('id')]);
        $this->assertSame(null, $field1->intvalue);
        $this->assertSame('', $field1->value);
        $field2 = $DB->get_record('customfield_data', ['fieldid' => $cf2->get('id')]);
        $this->assertSame('20', $field2->intvalue);
        $this->assertSame('20', $field2->value);

        // Negative number.
        $submitdata['customfield_myfield1'] = '-99999';
        $submitdata['customfield_myfield2'] = '20';
        core_customfield_test_instance_form::mock_submit($submitdata, []);
        $form = new core_customfield_test_instance_form('POST',
            ['handler' => $handler, 'instance' => $course1]);
        $this->assertFalse($form->is_validated());
    }

    /**
     * Test for data_controller::get_value and export_value
     */
    public function test_get_export_value() {
        /** @var \core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $cfcat = $generator->create_category();
        $course1 = $this->getDataGenerator()->create_course();
        $cfields1 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield1', 'type' => 'training']);
        $cfields2 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield2', 'type' => 'training',
                'configdata' => ['required' => true]]);
        $cfields3 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield3', 'type' => 'training',
                'configdata' => []]);

        $cfdata1 = $generator->add_instance_data($cfields1, $course1->id, 1);

        $this->assertSame(1, $cfdata1->get_value());
        $this->assertSame(1, $cfdata1->export_value());

        // Field without data but with a default value.
        $d = \core_customfield\data_controller::create(0, null, $cfields3);
        $this->assertSame(null, $d->get_value());
        $this->assertSame(null, $d->export_value());
    }

    /**
     * Deleting fields and data
     */
    public function test_delete() {
        /** @var \core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $cfcat = $generator->create_category();
        $cfields1 = $generator->create_field(
            ['categoryid' => $cfcat->get('id'), 'shortname' => 'myfield1', 'type' => 'training']);
        $cfcat->get_handler()->delete_all();
    }
}
