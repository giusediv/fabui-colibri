#!/bin/env python
# -*- coding: utf-8; -*-
#
# (c) 2016 FABtotum, http://www.fabtotum.com
#
# This file is part of FABUI.
#
# FABUI is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FABUI is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with FABUI.  If not, see <http://www.gnu.org/licenses/>.

__authors__ = "Krios Mane, Daniel Kesler"
__license__ = "GPL - https://opensource.org/licenses/GPL-3.0"
__version__ = "1.0"

# Import standard python module
import re

# Import external modules

# Import internal modules
from fabtotum.fabui.hardware.common import loadFactoryFeeder, updateFactoryFeeder, defaultPrismSettings
from fabtotum.utils.translation import _, setLanguage


def hardware4000(gcodeSender, config, log, eeprom, factory):
    """
    Rev4000 Prism: TBA - xxx
    """
    log.info("Rev4000 - Prism")
    defaultPrismSettings(gcodeSender, config, log, eeprom, factory)
    config.set('settings', 'hardware.id', 3000)
    config.save('settings')
