<?PHP

class flowSVG{
        
    const debug_active = False;
    
    const level_heigth = 85;
    const level_pad = 30;
    const element_width = 100;
    const element_height = 40;
    const left_offset = 5;
    
    const level_label_offset = 150;
    
    
    const analysis_round_x = 0;
    const analysis_round_y = 0;
    
    const anchor_round_x = 10;
    const anchor_round_y = 10;

    const text_offset_x = 22;
    const text_offset_y = 23;
    
    const connector_offset_x = 50;
    const connector_offset_y = 20;
        
    const analysis_style = 'fill:#F0F0F0;stroke:black;stroke-width:1;';
    const anchor_style = 'fill:#BCED91;stroke:black;stroke-width:1;';
    const math_style = 'fill:#00CCCC; stroke: #000000; stroke-width: 1; stroke-dasharray: 5 2';
    const comp_style = 'fill:#FFCC11; stroke: #000000; stroke-width: 1; stroke-dasharray: 5 2';
    
    const output_style = 'fill:#F0F0F0; opacity: 0.5';
    const output_text_style = 'font-family: Helvetica Neue, Helvetica, Arial, sans-serif; font-weight: normal; font-style: normal; font-size: 9px';
    
    const connector_style = 'stroke:rgb(0,0,0);stroke-width:1';    
    const normal_text = 'font-family: Helvetica Neue, Helvetica, Arial, sans-serif; font-weight: normal; font-style: normal; font-size: 10px';
    
    private $_svgContent = '';
    private $_levelContents = array();
    private $_xyArray = array();
    private $_maxX = 0;
    
    private function _debug($msg){
        
        if(self::debug_active === True){
            print '<script> console.log("' . $msg . '");</script>';
        }
    }
    
    private function _checkmaxX($x){
        if($x > $this->_maxX){
            $this->_maxX = $x;
        }
    }

    private function _addToBuffer($content, $front = False){
        if($front == True){
            $this->_svgContent = $content . $this->_svgContent;
        }else{
            $this->_svgContent .= $content;        
        }
        
    }
    
    private function _drawLevelLabels(){
        
        $x = $this->_maxX + self::level_label_offset;
        
        foreach($this->_levelContents as $level => $contents){
            
                        
            if($level == 1){
                $y = self::element_height / 2;               
            }
            
            if($level > 1){
                $y = ( $level - 1 ) * ( self::level_heigth + self::level_pad + ( self::element_height / 2) );                
            }
            
            $this->_drawText('Flow level: ' . $level, $x, $y, self::normal_text, False);
            
        }
        
    }
    
    private function _drawRect($id, $x, $y, $rx, $ry, $w, $h, $style, $level = False, $name = False){        
        $this->_checkmaxX($x);
        $this->_addToBuffer("<rect id='$id' x='$x' y='$y' rx='$rx', ry='$ry' width='$w' height='$h' style='$style' level='$level' name='$name'  />");        
    }
    
    private function _drawLine($id, $xS, $yS, $xE, $yE, $style, $stdOffset = True){        
        
        $this->_checkmaxX($xS);
        $this->_checkmaxX($xE);
        
        if($stdOffset == True){
            $xS = $xS + self::connector_offset_x;
            $xE = $xE + self::connector_offset_x;
            $yS = $yS + self::connector_offset_y;
            $yE = $yE + self::connector_offset_y;               
        }
        
        $this->_addToBuffer("<line id='$id' x1='$xS' y1='$yS' x2='$xE' y2='$yE' style='$style'/> ", True);                
    }
    
    private function _drawText($text, $x, $y, $style, $stdOffset = True, $offX = False, $offY = False){        
        
        $this->_checkmaxX($x);
        
        if($stdOffset == True){
            $x = $x + self::text_offset_x;
            $y = $y + self::text_offset_y;
        } else{
            if($offX != False){
                $x = $x + $offX;
            }
            if($offY != False){
                $y = $y + $offY;
            }
        }
        $this->_addToBuffer("<text x='$x' y='$y' fill='black' style='$style'>$text</text>");                 
    }
    
    private function _newGroup($id, $x , $y){
        
        $this->_checkmaxX($x);
        
        $this->_xyArray[$id]['x'] = $x;
        $this->_xyArray[$id]['y'] = $y;
        
        $this->_debug('group started:' . $id);
        $this->_addToBuffer('<g id="' . $id . '">');
    }
    
    private function _endCurrentGroup(){
        $this->_debug('group ended');
        $this->_addToBuffer('</g>');
    }
        
    private function _getY($level){
       
        //first level is just on 0
        if($level == 1){
            $y = 0;
        }
       
        //for next levels add the width of each line, plus its padding
        if($level > 1){
            $y = ( $level - 1 ) * ( self::level_heigth + self::level_pad );
        }
        
        $this->_debug('Y for this level:' . $y);
        
        return $y;
    }
    
    private function _getX($level){
        
        if(isset($this->_levelContents[$level])){
            $levelExists = True; 
        } else{
            $levelExists = False;
        }
        
        $this->_debug('Level exists is:' . $levelExists);
        
        //if nothing on this level yet, just give the Y the offset
        if($levelExists == False){
            $x = 0;
        } 
        
        //something is on this level, check how many and calculate the new offset        
        else{            
            $numberOfElements = count($this->_levelContents[$level]);
            $x = ( $numberOfElements * ( self::element_width + self::left_offset ) ) ;
        }
                                
        $this->_debug('X for element on level' . $level . ':' . $x);
        return $x;        
    } 
     
    public function addAnalysisComp($name, $id, $level){
                        
        $yForLevel = $this->_getY($level);
        $x = $this->_getX($level);
                
        //start a group
        $this->_newGroup( $id, $x, $yForLevel);
        $shapeId = 'fc_' . $id;
        
        //draw rect
        $this->_drawRect($shapeId, $x, $yForLevel, self::analysis_round_x, self::analysis_round_y, self::element_width, self::element_height, self::analysis_style, $level, $name);        
        $this->_drawText($name, $x, $yForLevel, self::normal_text);        
        $this->_endCurrentGroup();        
        $this->_levelContents[$level][$name . $id] = $id;
    }    
    
    public function addAnchor($name,$id, $level){
     
        $yForLevel = $this->_getY($level);
        $x = $this->_getX($level);
        
        $this->_newGroup( $id, $x, $yForLevel);
        $shapeId = 'fc_' . $id;
        
        $this->_drawRect($shapeId, $x, $yForLevel, self::anchor_round_x, self::anchor_round_y, self::element_width, self::element_height, self::anchor_style, $level, $name);        
        $this->_drawText($name, $x, $yForLevel, self::normal_text);        
        $this->_endCurrentGroup();        
        $this->_levelContents[$level][$name . $id] = $id;
    }
    
    public function addCustom($name,$id, $level){
     
        $yForLevel = $this->_getY($level);
        $x = $this->_getX($level);
        
        $this->_newGroup( $id, $x, $yForLevel);
        $shapeId = 'fc_' . $id;
        
        $this->_drawRect($shapeId, $x, $yForLevel, self::anchor_round_x, self::anchor_round_y, self::element_width, self::element_height, self::comp_style, $level, $name);        
        $this->_drawText($name, $x, $yForLevel, self::normal_text);        
        $this->_endCurrentGroup();        
        $this->_levelContents[$level][$name . $id] = $id;
    }
    
    public function addMathComponent($name, $id, $level){
        
        $yForLevel = $this->_getY($level);
        $x = $this->_getX($level);
        
        $this->_newGroup( $id, $x, $yForLevel);
        $shapeId = 'fc_' . $id;
        
        $this->_drawRect($shapeId, $x, $yForLevel, self::anchor_round_x, self::anchor_round_y, self::element_width, self::element_height, self::math_style, $level, $name);        
        $this->_drawText($name, $x, $yForLevel, self::normal_text);        
                    
            
        $this->_endCurrentGroup();        
        $this->_levelContents[$level][$name] = $id;
    }
    
    public function drawConnection($id, $origin, $target, $lineSending = False){
        
        $xS = $this->_xyArray[$origin]['x'];
        $yS = $this->_xyArray[$origin]['y'];
                
        $xE = $this->_xyArray[$target]['x'];
        $yE = $this->_xyArray[$target]['y'];
        
        $this->_newGroup('line_connex', $xS, $yS);        
        $this->_drawLine($id, $xS, $yS, $xE, $yE, self::connector_style);
        
        //info for next to line
        if($lineSending != False){
            
            $currentX = $xS + ( self::element_width / 2 ) - 20;
            $currentY = $yS + ( self::element_height + 10 );
            
            foreach($lineSending as $arbId => $name){            
                $this->_drawRect($arbId, $currentX, $currentY, '0',  '0', '40', '12', self::output_style);
                $this->_drawText($name, $currentX, $currentY, self::output_text_style, False, 5, 7);
                $currentY = $currentY + 14;
                
            }
        }
        
        $this->_endCurrentGroup();

    }
    
    public function generateClickScript(){
        
    }
    
    public function getBuffer(){
        $this->_drawLevelLabels();
        return $this->_svgContent;
    }
}