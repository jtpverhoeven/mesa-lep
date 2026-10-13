# Intro
We are transplanting our legacy application into laravel. What this means is that we are keepign all the idiosyncracies of the old code, but 
moving it into laravel. This will mean we will be using old database column names that might not be best practice, but, we want to port first, and optimze later.

# Refactor?
For the implementation, we can refactor. The old code has a lot of weird quirks due to it's propietary framework, which we are now ditching in favor of laravel. this means, that in some cases we can gain a lot of ground by refactoring with build in methods that laravel has available, specifically relationships.

# overall architecture?
We are keeping a "actions based" architecture. 

# This session build instructions

We now have to implement the backbone of printing. Printing, in this context, means the generation of labels. We use ZPL (zebra labels lanugage) and Zebra hardware. The print function is a multi facetted service, that does a *lot* of heavy lifting within the laboratory.

We should tackle this in multiple steps, in a way we dont regress from our legacy implementation, and dont create a spaghetti mess. This needs to be a unified service that has tendrils in all of our application, so it has to be DRY. 

We have already setup printers, label events and label designs. 

What we have to implement in this session  is the concept of PrintProfiles. Print profiles are essentially small plain text scripts that users can create, and call, to automate some sticker printing. For example, a script could be "does this sample have analysis XYZ, then goto line 40, line 40 = print label design I on printer J."

In this pass i would like the administrative side of this function to be implemented. That is; on the admin section of our application we will have a fully functional CRUD interface to create, edit, and delete print profiles. 

In addition, I would like you to implement, as a action that is delegated to a laravel job, so these profiles can be queued with the already implemented PrinterTransport, the parsing of these profiles. In addition, we should have a "Test" function, where the user can supply a barcode, and the output should show how many labels, tow hich printers, with which contact would have been printed (like a dry-run). 



# Legacy 

In our legacy implementation you can find this mostly in the function `runPrintProfile` in the file `legacy/app/controllers/printingController.php`. However, the location of this is subject to change. A dedicated model would be better (i.e. `PrintProfile`). BUT important: the existing data structure should remain compatible, the old print profiles *must* work in the new implementation. 



# Addendum
Focus on code deliverability. But implement tests for this, since it is a important section of the application. This application runs in laravel sail, so any commands you want to do for laravel have to move through there.  A MCP server is available (laravel boost). If you need to test inbrowser, use port 80, not 8000.

Focus on using OpenVue components over rolling your own, but style them with tailwind in the same theme as the rest of the application.