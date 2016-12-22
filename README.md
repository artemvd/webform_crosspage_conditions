# Webform Crosspage Conditions

This module provides the webform handler that could be added to the webform. The handler on alterForm hook tryies 
to evaluate the conditions to form fields which are reference from another pages. Because the default states work
only for the same page.

The module is not yet stable, but basic integration works. Supported conditions 'Value is', 'Checked' and 'Unchecked'.
Supported states are 'required', 'optional', 'visible', 'invisible', 'disabled'.

There is one drawback in the module. It uses php eval() to evaluate multiple conditions on 1 state. Need to find the 
way to substitute that with something more reasonable.