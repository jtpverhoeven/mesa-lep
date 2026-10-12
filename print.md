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

We have already setup printers, and print designs. 

Now we have to tackle LabelEvents. LabelEvents are a complicated piece of work, they basically allow the users to define conditional statements to define what labels are being printed in what quantity on which printer, that get executed through automatic triggers on sample registration, while adding analyses, or while approving a `best before (tht)` sample. 

In this pass, focus on the backbone and administrative part of this, with all the needed DRY and centralized entrypoints, which we can *later* hookup to sample registration etc. 

# Legacy 

See `legacy/app/controllers/labelEventsController.php` how to set this up, but please revamp the UI and interface, as long as the underlying function works the same and the data structures do not change. Focus on using OpenVue components over rolling your own, but style them with tailwind in the same theme as the rest of the application. 

# Addendum
Focus on code deliverability. But implement tests for this, since it is a important section of the application. This application runs in laravel sail, so any commands you want to do for laravel have to move through there.  A MCP server is available (laravel boost). If you need to test inbrowser, use port 80, not 8000.

Focus on using OpenVue components over rolling your own, but style them with tailwind in the same theme as the rest of the application.