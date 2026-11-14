# Roadmap

- Other calculations
    Need to check what we need to build.
        xlegionellaPassthrough.class.php
        xmaz7218.class.php
        xmaz7218grens.class.php
        xmazLegionella2019.class.php
        xmazKarkasKiem.class.php
        xmazKarkasEntero.class.php
        xmazLucht.class.php
        xrodac.class.php
        xmazVrij.class.php                
        xph.class.php
        
        xmazSchelp.class.php
        
        xmpn.class.php
        

        ?mazLegionella.class.php               
        

- Audit trail

- Status indicators

- Default printer

Data mining:

    - Resultaten uitvoeren

    - Rapportages

    - Deauthorisatie
    
    - User printers

# To verify
- Check if supporting media is always on in legacy? (i think they might be default: on)   
- agent reports "I added Quill's core CSS to the stylesheet. Without it the bullet markers didn't render.", perhaps this was just tailwind, and we are bloating the css? 

# Known bugs
- Should allow deleting individual analyses on sample create screens.
- Metadata change tracker error. puts from -> to as key->value
- Check legionella and rodac special cases in the new code for template selection once we get these things up and running
- Check UX on input of samplelookup, are we focusing properly on first fields
- Aantal monsters does not work in normal flow

# Ideas - Improvements after code base done
* Should input in assays be allowed  when sample not yet innoculated?
* ZPL render 
* Peek into projects (samples) for project overviews
* Printer connection test (if this is posible on zebra)
* Create a default test label 
* If a reference is set, we should also show in normal lims result if it's over the limit. 
* "Social" like ways of tagging users in basically any section of the application. 
* Locked projects should not be allowed to generate a report / temp report i think. Make cvar for behaviour?
* Shoudl show some feedback when authorizing a project that work is underway, and do a little SFX probbably.
* Aanmelden als shoudl conform to column width and only be half-size when tht selected.

# Phoenix verification script

* Use project,sample,result,confirmation data to simulate new entry from authorized projects, confirm with storedResult if the two systems are on-par. report failures

# Mesa-Retro

* 

# Result-Insight

Have a peek page, where every step of the calculation is actually shown and explained (including file locations and functions called with parametesr?). Not so much a small peek, but a true insight into how the calculation scripts work. woudl be extremely usefull to find edge cases etc. 