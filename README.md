# User Reporter CHG - Moodle Local Plugin

A lightweight administrative reporting tool for Moodle that displays all site users and the courses in which each one is enrolled.

Designed for simplicity, speed, and ease of use within the Moodle navigation. Made it for e-ABC Learning Challenge.

The “CHG” word it's a reference for “Challenge”.

Enjoy it.

---

## Overview

**User Reporter CHG** adds a system-level report that lists:

- Username
- First name
- Last name
- All enrolled courses (comma-separated list)

The report is sortable, paginated, and supports exports via Moodle’s native table download formats (CSV, Excel, etc.).

This plugin is intended for site managers, administrators, and staff who need an at-a-glance overview of user enrolments.

---

## Features

- System-wide user/course report
- Integration with Moodle navigation
- Export to CSV, Excel, ODS, etc.
- Pagination with configurable records per page
- Manual seeder script to generate demo users and courses
- Capability-based access control
- Fully documented codebase (PHPDoc + Moodle style)

---

## Folder Structure

local/user_reporter_chg/
│
├── classes/
│ └── table/
│         └── users_courses_table.php
│
├── cli/
│         └── demo_seeder.php
│
├── db/
│      ├── access.php
│
├── lang/
│      ├── en/local_user_reporter_chg.php
│      └── es/local_user_reporter_chg.php
│
├── index.php
├── lib.php
├── settings.php
└── version.php

---

## Capability

This plugin exposes a single capability:

| Capability | Description |
| --- | --- |
| `local/user_reporter_chg:view` | Allows users to view the user-course report |

Default roles that can access the report:

- **Manager**
- **Administrator**

These can be modified under:

> Site administration → Users → Permissions → Define roles
> 

---

## Navigation

Once installed, the report appears automatically under:

> Site administration → Reports → User Courses Report
> 

and also in the **left-hand navigation sidebar** (global navigation), if the user has permissions.

---

## Report Page

The report is located at:

/local/user_reporter_chg/index.php

Features:

- Sortable columns
- Adjustable `records per page` (3, 10, 25, 50)
- Export support using Moodle’s table_sql download handlers
- Clean and simple interface using Moodle core UI components

---

## Installation

1. Download or clone this repository into:

```php
/local/user_reporter_chg/
```

1. Log in as an Administrator.
2. Moodle will detect the plugin automatically.
3. Complete the installation via the web UI.

---

## Demo Data Seeder (Optional)

A CLI seeder is included to populate a development environment with sample data:

- 4 demo courses
- 50 demo users
- Enrol all users into all demo courses

---

## Requirements

- Moodle must be fully installed
- You must run it from the command line (CLI mode)
- Manual enrolment plugin must be enabled

This script is **intended strictly for development/testing**, not production.

---

## Requirements

- **Moodle 4.1 or higher**
- PHP version aligned with your Moodle environment
- Enabled *manual enrolment* plugin (for seeder only)

Plugin metadata:

```
component: local_user_reporter_chg
version:   2025111800
release:   1.0.0
maturity:  STABLE
```

---

## Troubleshooting

### The report does not appear in the navigation

- Ensure the user has the capability:
    
    `local/user_reporter_chg:view`
    
- Clear Moodle caches:
    
    *Site administration → Development → Purge caches*
    

### Courses do not display

- Verify user enrolments exist
- Confirm enrolment plugins are working
- Check that users are not deleted or suspended

### Seeder does not work

- Run via CLI only
- Ensure Moodle is installed and `$CFG->rolesactive` is set
- Confirm manual enrolment plugin is enabled

---

## Author

**Leon. M. Saia**

**leonmsaia@gmail.com**

[**https://leonmsaia.com**](https://leonmsaia.com/)

+54 11 2374 7372
