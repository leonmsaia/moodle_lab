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

namespace local_mutualreport\report;

use local_mutualreport\url;
use core\url as moodle_url;

class consolidado_v35 extends report_base {

    public function get_name(): string {
        return 'consolidado_v35';
    }

    public function get_title(): string {
        return get_string('report_elsa_consolidado_v35', 'local_mutualreport');
    }

    public function get_url(): moodle_url {
        return url::view_report_elsa_consolidado_v35();
    }

    public function get_group(): string {
        return report_base::GROUP_ELSA_35;
    }

}
