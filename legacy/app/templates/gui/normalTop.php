<div class="navbar navbar-fixed-top">
           <div class="navbar-inner">

               <?PHP
               if(isset($_SESSION['DEVELOPMENT']) && $_SESSION['DEVELOPMENT'] == True){
                    print '<div class="container-fluid" style="background-color: #ec971f;">';
                    print '<a class="brand" href="{LP}"  tabindex="-1" ><img src="{LP}/img/smallLogoMain.png"/> mesaLIMS <i class="icon-wrench"></i> DEVELOPMENT </a>';
               } else{
                   print '<div class="container-fluid">';
                    print '<a class="brand" href="{LP}"  tabindex="-1" ><img src="{LP}/img/smallLogoMain.png"/> mesaLIMS  </a>';
               }
               ?>




                   <ul class="nav">
                         <!-- <li class="{MESA_SOCIAL_ACTIVE}">
                            <a href="{LB}/labtalk/wall"  tabindex="-1" ><i class="icon-comments"></i> {MESA_TOP_LABTALK}</a>
                        </li> -->

                        <li class="{MESA_LIMS_ACTIVE}">
                            <a href="{LB}/lims/dashboard"  tabindex="-1" ><i class="icon-beaker"></i> {MESA_TOP_LIMS}</a>
                        </li>

                        <auth:adminSectionLink>
                        <li class="{MESA_ADMIN_ACTIVE} {PROD_ADMIN_HIDE}">
                            <a href="{LB}/admin/dashboard"  tabindex="-1" ><i class="icon-cogs"></i> {MESA_TOP_ADMINISTRATION}</a>
                        </li>
                        </auth>
                   </ul>

                   <ul class="nav pull-right">
                   <li class="">
                        <a href="{LB}/search/search" tabindex="-1" ><i class="icon-search"></i> Zoeken </a> 
                    </li>

                   <li class="">
                        <a  id="mediaPredictLink" onClick="openMediaPrediction()" tabindex="-1" ><i class="icon-beaker"></i> Media </a> 
                    </li>
         
                   <li class="dropdown messages-dropdown">
                            <a  tabindex="-1"  class="dropdown-toggle" class="ui-avatar" data-toggle="dropdown" href="#">
                                <i class="icon-inbox"></i> Inbox </a>
                            <ul id="inboxMsgList" class="dropdown-menu">

                            <li id="inboxLoader" class="message-preview">
                                <a href="#">
                                    <i class="icon-spinner icon-spin"></i> {MESA_CHT_LOADING}
                                </a>
                            </li>



                           <li class="divider"></li>
                           <li><a href="{LB}/chats/inbox"><i class="icon-inbox"></i> {MESA_CHT_OPENINBOX} </a></li>

                           <li class="dropdown-submenu">
                                <a tabindex="-1" href="#"><i class="icon-comment"></i> {MESA_CHT_OPENCHAT}</a>
                                <ul id="chatUsersOnline" class="dropdown-menu">

                                    <li >
                                        <a href="#">
                                            <span class="avatar"></span>
                                            <span class="name"><i class="icon-spinner icon-spin"></i> {MESA_CHT_LOADING} </span>
                                        </a>
                                    </li>

                                </ul>

                            </li>

                          </ul>

                       </li>

                       <li><a href="{LB}/profiles/view/{MESA_USR_USERNAME}" tabindex="-1"> {MESA_USR_FIRSTNAME} {MESA_USR_LASTNAME}</a></li>
                       <li class="dropdown" >
                            <a  tabindex="-1"  class="dropdown-toggle" class="ui-avatar" style="padding-top: 9px; padding-bottom: 9px; "  data-toggle="dropdown" href="#">

                                <div class="avaCrop">
                                    <img src="{MESA_USR_AVATAR}" class="avatar-32" style="padding-right: 4px"/>
                                </div>





                                <span class="badge badge-success"></span></a>
                            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                            <li>
                                <a href="#" onClick="openLabjournal();"><i class="icon-book"></i> {MESA_UDD_LABJOURNAL}</a>
                            </li>

                            <li>
                                <a href="#" onClick="copyToLabJournal();"><i class="icon-copy"></i> {MESA_UDD_COPYTOLAB}</a>
                            </li>

                            <li>
                                <a href="#" onClick="openAcountSettings();"><i class="icon-cogs"></i> {MESA_UDD_ACOUNTSETTINGS}</a>
                            </li>
                            <li class="divider"></li>

                            <li>
                                <a href="#" onClick="openDebugConsole();"><i class="icon-terminal"></i> Debug console</a>
                            </li>

                            <li class="divider"></li>
                            <li>
                                <a href="{LB}/?logout"><i class="icon-lock"></i> {MESA_UDD_LOGOUT}</a>
                            </li>
                          </ul>

                       </li>
                   </ul>
               </div>
           </div>
    </div>
