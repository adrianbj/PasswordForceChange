PasswordForceChange
===================

Processwire module to force users to change password the first time they log in

Key Features

* During install it creates a new checkbox field in the user template, "passwd_changed", and checks this for all existing users so they are not hassled if you add this module to an existing site. Obviously for all new users after the module is installed, this field will be unchecked and so the password change will be enforced.
* When a user logs in for the first time (or if you have manually unchecked that field for an existing user), they will be warned that they have to change their password and they'll be automatically redirected to their profile page.
* They must change their password to something new - they are not allowed to re-enter their existing password.

###Support
https://processwire.com/talk/topic/7043-password-force-change/

## License

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

(See included LICENSE file for full license text.)