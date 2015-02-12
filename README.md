# instagram2diaspora

put your instagrams to diaspora *yay*

## Install

- copy config.php.dist to config.php
- composer install
- php ig2dstar.php (Warning: Running this initialy will post 20 pics)
- setup a cron job to to post your stuff to diaspora all the time

## Warning

This is not ready for /production/ as there is some bug that the CSRF-token cant be verified. I put some nasty stuff in my 'app/controllers/status_messages_controller.rb' to skip CSRF. But if some knows how to handle this properly let me know.