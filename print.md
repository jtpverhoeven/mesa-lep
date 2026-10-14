# Intro
We are transplanting our legacy application into laravel. What this means is that we are keepign all the idiosyncracies of the old code, but 
moving it into laravel. This will mean we will be using old database column names that might not be best practice, but, we want to port first, and optimze later.

# Refactor?
For the implementation, we can refactor. The old code has a lot of weird quirks due to it's propietary framework, which we are now ditching in favor of laravel. this means, that in some cases we can gain a lot of ground by refactoring with build in methods that laravel has available, specifically relationships.

# overall architecture?
We are keeping a "actions based" architecture. 

# This session build instructions

We now have to implement the front-end of the printing architecture in our application. So far, we have already implemented printers, a print queue system, event driven settings, as well as print profiles. What we now have to create, is a front-end that resembles *exactly* the legacy view of `legacy/app/views/printing/s2p.php` with our new skin ofcourse. 

This page has multiple ways of printing, and that needs to be retained. 

1) There is the normal `Scan barcode` way, which prints a specific barcode, in conjunction with the middle column `Print opties` and `Geavanceerde opties`. The has  the option to select multiple print profiles, as well as additional label `Print ook` and `monster labels` as wel as selecting which printer to send this to (or default printer from the profile or label itself)

2) There is the `Print datum bereik` which selects a range first based on a type of date, grabs the applicable samples, and runs all the selected options (similar to #1) for that range.

3) There is the `Print monster bereik`, which is a range, but based on a barcode range, with the last barocde being included in the range. It has an additional option of subsetting on normal and legionella samples, and rodac samples. 

4) There is a `Datum instellen`. Since sometimes, people may want to "preprint"  samples for another day. So this date will override the Innoculation day shown on the labels. This should (i think) alreayd be implemented in the back-end of our new printing system, so we should just have to plumb this in. 

5) When the user accidentally scans a range that is huge, bigger then our `App\Model\Cvar` with the name `MAX_LABEL_PRINT` we should first ask the user "You are about to print X labels, ar eyou sure?".

Please *carefully* investigate this page, and make sure we *do not regress* or *change* how this printing *behaviour* works. Since this is vital for how our laboratory works. 

# Out of scope notice

There is also a box that allows users to print, seperatly, sheets of "Legionella" stickers. This you should not implement yet, since I have plans for modernizing this, so this will be done in a later pass. But please leave a placeholder for it. 

# Addendum
Focus on code deliverability. But implement tests for this, since it is a important section of the application. This application runs in laravel sail, so any commands you want to do for laravel have to move through there.  A MCP server is available (laravel boost). If you need to test inbrowser, use port 80, not 8000.

Please use Vue components for implementation. If needed, you can create a Pinia store if it is handy. 

Focus on using OpenVue components over rolling your own, but style them with tailwind in the same theme as the rest of the application.