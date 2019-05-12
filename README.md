# JTool
Server management tool for Super Entity Game Servers of all kinds.


I'm not great at this so let's keep it brief:

WHAT YOU NEED: A CoH Server with MSSQL (they all should use this right?), some sort of web server (probably APACHE), and PHP installed and configured

AND THEN: Place the JTools files in your root web folder (usually HTDOCS)

AND THEN: Your server has an IP. Use that IP in a web browser from any computer on your network that can reach your server. Or you can run it locally by typing "localhost/jtool" or "127.0.0.1/jtool" into the server's web browser.



***************** WARNING ********************
DarkSynopsis is the author of CoHDBTool and I left the import/export character code almost untouched because it's complex and I didn't verify that the list of exported tables is 100% accurate. It should work well between servers, but I can't make guarantees.


***************** WARNING ********************
***************** WARNING ********************
***************** WARNING ********************
***************** WARNING ********************
You should ABSOLUTELY turn off your DBserver before making changes or you may get inconsistent results.

AND: Like seriously; back up your DB and or server. I did the best I was able and the CoHDBTool code I built this from appears solid and reliable, but there are no guarantees. Best to be safe and keep your data backed up.
