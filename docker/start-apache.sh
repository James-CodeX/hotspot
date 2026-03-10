#!/bin/sh
set -eu

# Railway images occasionally surface duplicate MPM enables.
# Force Apache back to a single prefork MPM before startup.
rm -f /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf

if [ ! -e /etc/apache2/mods-enabled/mpm_prefork.load ]; then
    ln -s ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
fi

if [ ! -e /etc/apache2/mods-enabled/mpm_prefork.conf ]; then
    ln -s ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
fi

exec apache2-foreground