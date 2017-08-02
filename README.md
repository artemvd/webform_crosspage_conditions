# Webform Crosspage Conditions

This module provides the webform handler that could be added to the webform. The handler on alterForm hook tries 
to evaluate the conditions to form fields which are referenced from another pages. Because the default states work
only for the same page.

The module is not yet stable, but basic integration works. Supported conditions are: 'Value is', 'Checked' and 'Unchecked'.
Supported states are 'required', 'optional', 'visible', 'invisible', 'disabled'.

There is one drawback in the module. It uses php eval() to evaluate multiple conditions on one state. Need to find the 
way to substitute that with something more elegant.

# Make it work

This module provides only webform handler and its functionality is not applied to all webforms by default. To make it work you need to navigate to "Emails/Handlers" tab on your webform edit screen and click on "Add handler" button, select "Webform crosspage conditions" handler from the list and "Save". Only after this your webform will support the crosspage conditions check.
