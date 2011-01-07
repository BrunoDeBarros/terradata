<?php /* @var $this TD_Layout */?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo ($this->Title) ? $this->Title : "Powered by Terra Duo";?></title>
        <?php echo $this->TopIncludes();?>
    </head>
    <body>
        <div id="header">
            <div class="container">
                <?php if (isset($this->LoggedIn)) {?>
                <div id="login">
                        <?php if ($this->LoggedIn) {?>
                    You are logged in as <?php echo $this->Username;?>. <a href="<?php echo $this->LogoutLink?>">Log out?</a>
                            <?php } else {?>
                    You are not logged in. <a href="<?php echo $this->loginLink?>">Log in</a>
                            <?php }?>
                </div>
                    <?php }?>
                <h1><a href="<?php echo $this->HomeLink;?>"><?php echo ($this->Title) ? $this->Title : "Powered by Terra Duo";?></a></h1>
                <?php if (isset ($this->LoggedIn) and $this->LoggedIn) {?>
                    <?php if(count($this->NavigationLinks) > 0) {?>
                <ul>
                            <?php foreach($this->NavigationLinks as $link) {?>
                    <li><a href="<?php echo $link['href']?>" title="<?php echo $link['title']?>"><?php echo $link['innerHTML']?></a></li>
                                <?php }?>
                </ul>
                        <?php }?>
                    <?php }?>
            </div>
        </div>
        <div id="contents">
            <div id="messages">
                <?php if(!empty($this->success)) {?>
                <span class="success"><?php echo $this->success;?></span><br /><br />
                    <?php }?>
                <?php if(!empty($this->error)) {?>
                <span class="error"><?php echo $this->error;?></span><br /><br />
                    <?php }?>
            </div>
            <?php echo $contents;?>
        </div>
        <?php if ($this->Footer) {?>
        <div id="footer">
                <?php echo $this->Footer;?>
                <?php if ($this->ShowExtraInfo) {?>
            <br />
            <p>Generated in <strong><?php echo $this->getTimeSinceStart()?></strong> seconds 
                (<strong><?php echo round(($this->TimeSpentQuerying / $this->getTimeSinceStart()) * 100)?>%</strong> of it was spent querying the database)
                        <?php echo (function_exists('memory_get_peak_usage')) ? ', used a maximum of <strong>'.round(memory_get_peak_usage(true) / 1024).'KB</strong> of memory and' : ' and';?> executed <strong><?php echo $this->QueryCount;?></strong> queries.<br />The last query executed was: <strong><?php echo $this->LastQuery;?></strong></p>
                    <?php }?>
        </div>
            <?php }?>
        <?php echo $this->BottomIncludes();?>
    </body>
</html>