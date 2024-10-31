=== OkuPanel ===
Contributors: moon155
Tags: calendar, events, ics, ical, squat, ocupy, okupa, self-organization, kiosk, panel
Requires at least: 4.0
Tested up to: 4.9.4
Stable tag: trunk
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Live screen and online system to display federated events and rooms in self-organized spaces. By OkuLabs.


== Features ==

- Display events from one or several online calendars (.ics, iCalendar, Google Calendar..)
- Events are automatically updated on the client via periodic ajax calls so they are always up-to-date.
- Autodetect specific events and show them in the sidebar.
- Display a popup for every event, with full address and description.
- Fully responsive frontend.
- Auto-scrolling events in fullscreen mode (for days ahead).
- Auto-scrolling bottom bar that can be updated without reloading the page.
- Events can be included in any frontend content (page) via the [okupanel] shortcode, integrating your design.
- ICS output to subscribe to all your events (app synchronization).
- Graphical timeline of all events via the [okupanel_timeline] shortcode.
- Automatic management of cafeteria turns via an etherpad.
- Virtual nodes to show different centers in one common URL/screen.
- Federation capabilities to show events from many okupanels at once via a switch button.
- Add hashtags to events based on custom patterns (for special events).
- Available in English and Spanish. Can be translated to any language through .po files.
- Can be set as the root page of the wordpress (for dedicated domains).
- Coming soon: integrate events with OpenStreetMaps

See [a live OkuPanel](https://ingobernable.net/okupanel/). Or [this other one](https://www.evarganzuela.org/okupanel/).

[OkuPanel](https://code.cdbcommunications.org/okulabs/okupanel), a project [inited](https://wiki.ingobernable.net/doku.php?id=pantalla_entrada) at the [Ingoberlab](https://hacklab.ingobernable.net), now maintained by [OkuLabs](https://code.cdbcommunications.org/okulabs) via [cDb Communications](https://cdbcommunications.org).

== Installation ==


**Plugin installation:**

- Extract the plugin archive to your server's `wp-content/plugins` folder or install it via the regular `Plugins` page.
- Enable the OkuPanel plugin via the `Plugins` page.
- Go to `Settings > OkuPanel` and follow the instructions.

**Physical screen installation (optional):**

Requirements:

- A nice screen, ideally with an HDMI input.
- A computer, ideally a [Raspberry Pi3 Model B](https://www.raspberrypi.org/products/raspberry-pi-3-model-b/) or alike, that can be dedicated to the task, connect with your screen and to the internet. 
- A cable to connect the computer with the screen.
- A [good charger](https://www.raspberrypi.org/products/raspberry-pi-universal-power-supply/) if you opted for a Raspberry.
- A 8GB+ MicroSD card (better class 10, though it might work with a class 4), if you opted for a Raspberry.
- A regular computer with a MicroSD card reader and a connection to the internet.

Instructions:

- Write down the fullscreen URL that display in the `Settings > OkuPanel` page (it ends up with `?fullscreen=1&moving=1`).
- [Download FullpageOS](https://github.com/guysoft/FullPageOS) and extract it somewhere on your machine (with `unzip -u thefile` for example).
- Plug your MicroSD card to your computer and find its mounted path (for example with `sudo gparted`). Please make sure you use the right path, and not your local HDD path! Otherwise you could wipe out all your local disk.
- Burn the extracted .img on the MicroSD card (for example with `dd if=/path/to/the/image.img of=/dev/microsd_id bs=1M`).
- Once done, eject and re-insert the MicroSD card in order to mount it to your computer.
- On the "boot" partition of the MicroSD card, edit `fullpageos-network.txt` and put your network settings.
- Edit `fullpageos.txt` and leave only your OkuPanel's fullscreen URL.
- Edit `fullpagedashboard.txt` and leave only your OkuPanel's fullscreen URL there again.
- Eject the MicroSD card, insert it into your Pi, plug the Pi to a screen, and boot it.
- In a couple of minutes you should see the Pi automatically start Chromium in fullscreen mode and display your OkuPanel page ;)
- Additionally, you may want to log into your Pi via SSH (it may be located at pi@fullpageos.local, default password is "raspberry") to change the system password (using ```passwd```) or to set up a wifi access with [wicd](https://launchpad.net/wicd). 

[OkuPanel](https://code.cdbcommunications.org/okulabs/okupanel), a project [inited](https://wiki.ingobernable.net/doku.php?id=pantalla_entrada) at the [Ingoberlab](https://hacklab.ingobernable.net), now maintained by [OkuLabs](https://code.cdbcommunications.org/okulabs) via [cDb Communications](https://cdbcommunications.org).


== Frequently Asked Questions ==


**My panel is not reflecting the changes I make to the events, what should I do?**
- OkuPanel retrieves the events every 5 minutes, so it is normal if you don't see your changes immediately, just wait 5 minutes for fullscreen mode, or 15 for browser mode. If you need to force the panel to reflect the changes you just made (due to a mistake, or just because you're testing), you can always add ?update=1 to your OkuPanel URL while logged in, this will force the events to be retrieved again.

**Do you plan to add other languages?**
- No, but if you send us translation files (.po), we can add them to the plugin's available languages.

**Do you offer installation support?**
- Not really.. but you can always contact us on our [Matrix channel](https://matrix.cdbcommunications.org/) (room: #okupanel) or via email at okupanel@riseup.net.

**Do you have a donate link?**
- Sure! You can show us your support via this [Donate Page](https://donate.cdbcommunications.org). For any doubt, do not hesitate in contacting us at okupanel@riseup.net.

**Can you give us sample values for the config fields?**
- Sure. We compiled some sample configuration values [there](https://code.cdbcommunications.org/okulabs/okupanel/blob/master/SAMPLE-CONFIG.md).



== Screenshots ==
Desktop version of an OkuPanel
An event popup from the web version
A photo from a real OkuPanel entrance screen
Mobile version of an OkuPanel
