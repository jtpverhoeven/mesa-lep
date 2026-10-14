# Intro
We are transplanting our legacy application into laravel. What this means is that we are keepign all the idiosyncracies of the old code, but 
moving it into laravel. This will mean we will be using old database column names that might not be best practice, but, we want to port first, and optimze later.

# Refactor?
For the implementation, we can refactor. The old code has a lot of weird quirks due to it's propietary framework, which we are now ditching in favor of laravel. this means, that in some cases we can gain a lot of ground by refactoring with build in methods that laravel has available, specifically relationships.

# overall architecture?
We are keeping a "actions based" architecture. 

# This session build instructions

We now have to implement the action driven events within our architecture. That is to say, the moments where we invoke LabelEvents that the user has set up. Since we have already implemented the back-end of this, this session should be about plumbing up this back-end events system to the appropraite actions. 

In our legacy implementation these are run through the following functions in `legacy/app/controllers/printingController.php`: 

- `assayAddFire()` within the `addNewResearch()`function.
- `batchTHTReg()`  `thtRegFire()`, `sampleRegFire()` , `batchProcess()`. 

This is a bit of a mess, and should be refactored to work nicely with our new queued printing system, but, the functionality of all these labels need to remain the same. 


# Addendum
Focus on code deliverability. But implement tests for this, since it is a important section of the application. This application runs in laravel sail, so any commands you want to do for laravel have to move through there.  A MCP server is available (laravel boost). If you need to test inbrowser, use port 80, not 8000.

Focus on using OpenVue components over rolling your own, but style them with tailwind in the same theme as the rest of the application.