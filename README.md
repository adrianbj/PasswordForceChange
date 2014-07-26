PasswordForceChange
===================

Processwire module to force users to change their password.

Key Features

* During install it creates a new checkbox field in the user template, "force_passwd_change".
* Automatic checking of this checkbox when creating a new user is determined by the "Automatic Force Change" module config setting.
* When a user logs in for the first time (or if you have manually checked that field for an existing user), they will be warned that they have to change their password and they'll be automatically redirected to their profile page.
* They must change their password to something new - they are not allowed to re-enter their existing password.
* Bulk "Set All Users" option to at any time, force all users (by selected roles) to change their password.

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