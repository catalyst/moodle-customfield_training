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

/**
 * Training settings.
 *
 * @package   customfield_training
 * @copyright 2024 Open LMS (https://www.openlms.net/)
 * @author    Petr Skoda
 */

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */

$ADMIN->add('customfieldsettings', new admin_externalpage('customfield_training_frameworks',
    get_string('manageframeworks', 'customfield_training'),
    new moodle_url('/customfield/field/training/management/index.php'),
    'customfield/training:viewframeworks'));

// Do not use standard settings page
$settings = null;