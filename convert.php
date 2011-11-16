<?php
/**
 * This file is simply a playground for converting the xml version of an scc
 * pdf class schedule into actionable data for seaching purposes.
 * 
 * The end goal is to place the data into an sql database and provide 
 * a search better than scc's current search, including a list of the data
 * with links to the prereqs for each course.
 * 
 * This data is hard to read to pharase.  There is a good possibility that 
 * each new schedule might convert a little differently.  if you can think
 * of a better way to do this, please let me know.
 */

//display errors.
ini_set('display_errors', true);
error_reporting(E_ALL);

//Config vars
$pages          = range(13, 62);
$dataFile       = file_get_contents(dirname(__FILE__) . "/data/test.xml");
$collegeFont    = 1;
$courseLabStart = array(90, 91, 63, 64);
$courseStart    = array(81, 82, 54, 55);
$prereqStart    = array(307, 308, 334);
$prereqFont     = 21;
$courseFont     = 21;
$synonymStart   = array_merge($courseLabStart, $courseStart);
$synonymFont    = 11;

function compileClassData($details, $previousDetails)
{
    $data = $previousDetails;
    //sift though data to find the correct data.
    
    //TODO: do a lot of regex magic on the details string...
    //print_r($details);
    //print_r($previousDetails);
    
    return $data;
}

$data = new SimpleXMLElement($dataFile);

$currentDepartment = false;
$currentCourse     = false;
$currentSynonym    = false;
$left              = false;
$previousDetails   = array();
$synonymDetails    = false;

foreach ($data->page as $id=>$page) {
    if (!in_array($page['number'], $pages)) {
        continue;
    }
    
    foreach ($page->text as $id=>$text) {
        //is it a college?
        if (($text['font'][0] == 1) && !empty($text->b)) {
            $currentDepartment = $text->b;
            echo "College: " . $text->b . "<br>";
        }
        
        //is it a new course?
        if ((in_array($text['left'][0], $courseStart)
            || in_array($text['left'][0], $courseLabStart))
            && !empty($text->b)
            && $text['font'] == 21)
        {
            $currentCourse = $text->b;
            echo "----course: " . $text->b . "<br>";
        }
        
        //Do we have prereqs for the new course?
        if (in_array($text['left'][0], $prereqStart)
            && !empty($text->b)
            && $text['font'] == 21)
        {
            echo "--------requirements: " . $text->b . "<br>";
        }
        
        if (!empty($text[0])) {
            $text[0] = trim($text[0]);
        }
        
        //Do we have a new synonym?
        if (in_array($text['left'][0], $synonymStart)
            && !empty($text[0])
            && $text !== ' '
            && $text['font'] == 11)
        {
            if (!preg_match("([\d]{5})", $text[0])) {
                continue;
            }
            
            $currentSynonym = $text[0];
        }
        
        //Is the the same synonym but different details?
        if ($left
            && $left >= (int)$text['left'][0]
            && $text['font'] == 11
            && $text[0] !== " "
            && !empty($text[0])
            && $currentSynonym)
        {
            //This is a new synonym.
            if ($synonymDetails) {
                $previousDetails = compileClassData($synonymDetails, $previousDetails);
            }
            $synonymDetails = "";
            echo "--------synonym[new]: " . $currentSynonym . "<br>";
        }
        
        if ($text['font'] == 11
            && $text[0] !== " "
            && !empty($text[0]))
        {
            $left = $text['left'];
            $synonymDetails .= " " . $text[0];
            echo "------------details: " . $text[0] . "<br>";
        }
    } 
}
?>