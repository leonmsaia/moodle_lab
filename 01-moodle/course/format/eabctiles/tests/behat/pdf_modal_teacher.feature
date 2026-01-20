@format @format_eabctiles @format_eabctiles_mod_modal @format_eabctiles_pdf_modal_teacher @javascript @_file_upload
Feature: PDFs can be set to open in modal windows with subtiles off
  In order to improve UX
  As a user
  I need to be able to use these modals

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |
      | Course 1 | C1        | tiles  | 0             | 5           | 1                |
    And the following "activities" exist:
      | activity | name           | intro                 | course | idnumber | section | visible | completion | defaultfilename                             | uploaded |
      | page     | Test page name | Test page description | C1     | page1    | 1       | 1       | 0          |                                             | 0        |
      | resource | Test PDF       | File description      | C1     | pdf1     | 1       | 1       | 1          | course/format/eabctiles/tests/fixtures/test.pdf | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | config                 | value    | plugin       |
      | enablecompletion       | 1        | core         |
      | modalmodules           | page     | format_eabctiles |
      | modalresources         | pdf,html | format_eabctiles |
      | assumedatastoreconsent | 1        | format_eabctiles |
      | reopenlastsection      | 0        | format_eabctiles |
      | usejavascriptnav       | 1        | format_eabctiles |

    When I log in as "teacher1"
    And format_eabctiles subtiles are off for course "Course 1"
    And I am on "Course 1" course homepage

    And I am on "Course 1" course homepage with editing mode on
    Then I should see "Test PDF"
    And I log out tiles

  #  First check can see the PDF with subtiles off
  @javascript
  Scenario: Open section 1 view PDF as teacher with subtiles off
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And format_eabctiles subtiles are off for course "Course 1"
    And I click on tile "1"
    And I wait until the page is ready
    And I click format tiles activity "Test PDF"
    And I wait until the page is ready
    Then "Test PDF" "dialogue" should be visible

    And format_eabctiles progress for "resource" called "Test PDF" in "Course 1" is "0" in the database
    And I click on "Mark as done" "button" in the "Test PDF" "dialogue"
    And I wait until the page is ready
    And format_eabctiles progress for "resource" called "Test PDF" in "Course 1" is "1" in the database
    And I click on "Done" "button" in the "Test PDF" "dialogue"
    And I wait until the page is ready
    And format_eabctiles progress for "resource" called "Test PDF" in "Course 1" is "0" in the database

    And "Close" "button" should exist in the "Test PDF" "dialogue"
    And I click on "Close" "button" in the "Test PDF" "dialogue"
    And I wait until the page is ready
    And "Test PDF" "dialogue" should not be visible
    And I click on close button for tile "1"

#  Now with subtiles on
  @javascript
  Scenario: Open section 1 add PDF as teacher with subtiles on
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And format_eabctiles subtiles are on for course "Course 1"
    And I click on tile "1"
    And I wait until the page is ready
    And I click format tiles activity "Test PDF"
    And I wait until the page is ready
    Then "Test PDF" "dialogue" should be visible

    And format_eabctiles progress for "resource" called "Test PDF" in "Course 1" is "0" in the database
    And I click on "Mark as done" "button" in the "Test PDF" "dialogue"
    And I wait until the page is ready
    And format_eabctiles progress for "resource" called "Test PDF" in "Course 1" is "1" in the database
    And I click on "Done" "button" in the "Test PDF" "dialogue"
    And I wait until the page is ready
    And format_eabctiles progress for "resource" called "Test PDF" in "Course 1" is "0" in the database

    And "Close" "button" should exist in the "Test PDF" "dialogue"
    And I click on "Close" "button" in the "Test PDF" "dialogue"
    And I wait until the page is ready
    And "Test PDF" "dialogue" should not be visible
