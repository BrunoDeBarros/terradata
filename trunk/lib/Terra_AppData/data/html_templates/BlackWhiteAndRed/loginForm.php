<form method="post" action="<?php echo $this->LoginLink?>">
    <div class="form">
        <label for="username">Username</label>
        <input type="text" name="USERNAME" id="username" />
        <label for="password">Password</label>
        <input type="password" name="PASSWORD" id="password" />
        <label for="remember_me">Remember me?</label>
        <div class="radio"><label><input type="radio" name="REMEMBER_ME" id="remember_me" value="1" checked="checked" /> Yes</label></div>
        <div class="radio"><label><input type="radio" name="REMEMBER_ME" id="no_remember" value="0" /> No</label></div>
        <button type="submit">Log in</button>
    </div>
</form>