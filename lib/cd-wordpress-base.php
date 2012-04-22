<?php

class CheckdomainWordpressBase
{
    
    const MESSAGE_UPDATED  = 'updated';
    const MESSAGE_INFO     = 'information';
    const MESSAGE_ERROR    = 'error';
    
    /**
     * Creates a html message
     *
     * This method creates a html message to display in this plugin
     *
     * @param string $message
     * @param string $type
     */
    public function viewHelperMessage($message, $subject = NULL, $type = self::MESSAGE_UPDATED)
    {
        switch ($type) {
            case self::MESSAGE_ERROR:
                $class = 'error';
                break;
                
            case self::MESSAGE_INFO:
                $class = 'info';
                break;
                
            case self::MESSAGE_UPDATED:
                $class = 'updated';
                break;
        }
?>
    <div class="<?php echo $class ?>">
        <?php if ($subject !== NULL): ?>
            <strong><?php echo $subject; ?>: </strong>
        <?php endif; ?>
        <p>
            <?php echo $message; ?>
        </p>
    </div>
<?php
    }
    
}