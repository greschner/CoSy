<?php
declare(strict_types=1);

require('TagData.php');

/**
 * Parses HTML file to first Order JSON
 *
 * next class converts it to internal data structure
 */
class Parser
{
    private $file;
    private $fileLength;

    private $data;
    private $referenceStack;

    private $first = null;
    private $last = null;
    private $quotationMark = null;
    private $tagIncludesEnd = false;

    private $textStart = null;
    private $textEnd = null;

    private $inTag = false;

    private $loopPosition = 0;

    private $spaceCharacter = null;

    /**
     * Constructor
     *
     * @param string $file_path URL or FilePath in Directory (file_get_contents)
     */
    public function __construct($file_path)
    {
        #get file and get relevant part (dont need footer, header, ...)
        $file_string = file_get_contents($file_path);
        $start_pos = strpos($file_string, "<div data-start=\"1\" class=\"results-list\">"); #here starts the result list
        $end_pos = strpos($file_string, "<div class=\"d-flex justify-content-center\">"); #end of the result list
        $file_string = substr($file_string, $start_pos + 45, ($end_pos - $start_pos - 45)); #45 is length of needle

        #remove linebreaks in file
        $file_string = str_replace("\n", "", $file_string);
        $file_string = str_replace("\r", "", $file_string);
        $file_string = str_replace("\t", "", $file_string);

        $old_file_string = null;
        while ($old_file_string !== $file_string) {
            $old_file_string = $file_string;
            $file_string = str_replace("  ", " ", $file_string);
        }
        $file_string = str_replace("> ", ">", $file_string);
        $file_string = str_replace(" <", "<", $file_string);

        $this->file = $file_string;
        $this->fileLength = strlen($this->file);
    }

    public function init()
    {
        $this->data = new TagData("body", null);

        $this->referenceStack = [];
        array_push($this->referenceStack, $this->data);
    }

    public function parseText()
    {
        #go to opening tag
        for (; $this->loopPosition < $this->fileLength; ++$this->loopPosition) {
            if ($this->file[$this->loopPosition] == '<') {
                $this->first = $this->loopPosition;
                $this->inTag = true;
                break;
            }
        }
        #increment to next position
        ++$this->loopPosition;
        $this->readStartTag();

        #$json = json_encode($this->data, JSON_PRETTY_PRINT);
        #echo $json;

        $this->extractRelevantText();
    }

    private function extractRelevantText()
    {
        $result_array = [];

        #copy every discovered object to new object to get better json
        foreach ($this->data->cont as $result) {
            $entry = [];
            try {
                if (isset($result->cont[0]->cont[1]->cont[0])) {
                    $entry["heading"] = $result->cont[0]->cont[1]->cont[0];
                }
            } catch (Exception $e) {
            }
            try {
                if (isset($result->cont[0]->cont[1]->attr["href"])) {
                    $entry["link"] = "https://www.saferinternet.at" . $result->cont[0]->cont[1]->attr["href"][0]; #links on website are relative
                }
            } catch (Exception $e) {
            }
            try {
                if (isset($result->cont[1]->cont[0]->cont[0])) {
                    $entry["abstract"] = $result->cont[1]->cont[0]->cont[0];
                }
            } catch (Exception $e) {
            }
            if (!empty($entry)) {
                array_push($result_array, $entry);
            }
        }

        #$json = json_encode($this->data, JSON_PRETTY_PRINT);
        #echo $json;

        #save new object
        $this->data = $result_array;
    }

    private function readStartTag()
    {
        #check tag
        for (; $this->loopPosition < $this->fileLength; ++$this->loopPosition) {
            switch ($this->file[$this->loopPosition]) {
                case "\"":
                    if ($this->quotationMark == null) {
                        $this->quotationMark = "\"";
                    } elseif ($this->quotationMark == "\"") {
                        $this->quotationMark = null;
                    }
                    break;
                case '\'':
                    if ($this->quotationMark == null) {
                        $this->quotationMark = "'";
                    } elseif ($this->quotationMark == "'") {
                        $this->quotationMark = null;
                    }
                    break;
                case ' ':
                    if ($this->spaceCharacter == null) {
                        $this->spaceCharacter = $this->loopPosition;
                    }
                    break;
                case '/':
                    #character appears not in a string and next one is closing tag
                    if ($this->quotationMark == null && $this->file[$this->loopPosition + 1] == '>') {
                        $this->tagIncludesEnd = true;
                    }
                    break;
                case '>':
                    #character appears not in a string:
                    if ($this->quotationMark == null) {
                        $this->last = $this->loopPosition;
                        $this->inTag = false;
                    }
                    break;
                default:
                    break;
            }
            #discoverd end:
            if ($this->last != null)
                break;
        }

        #make sth according to tag data
        if ($this->last != null) {
            #get tag data:
            $tag_data = substr($this->file, $this->first + 1, ($this->last - $this->first));
            $attributes = [];

            if ($this->spaceCharacter != null) {
                $space_pos = strpos($tag_data, " ");
                $attributes = $this->readAttributes(substr($tag_data, $space_pos + 1));
                $tag_data = substr($tag_data, 0, $space_pos);
            } elseif ($this->tagIncludesEnd) {
                $tag_data = substr($tag_data, 0, -1);
            }
            $tag_data = preg_replace("/>/", "", $tag_data);

            if (strlen($tag_data) > 0) {
                #Tag is not the end
                if (!$this->isUnresolvedTag($tag_data)) {
                    $text_elem = $this->getText();
                    if ($text_elem !== null) {
                        array_push(end($this->referenceStack)->cont, $text_elem);
                    }
                    $tag_elem = new TagData($tag_data, $attributes);
                    array_push(end($this->referenceStack)->cont, $tag_elem);
                    if (!$this->tagIncludesEnd) {
                        array_push($this->referenceStack, $tag_elem);
                    }
                    #set pointer for text Element to new position:
                    $this->textStart = $this->loopPosition + 1;
                }
            }

            $this->spaceCharacter = null;
            $this->tagIncludesEnd = null;
            $this->first = null;
            $this->last = null;
        }
        #increment to next position
        ++$this->loopPosition;
        if ($this->loopPosition < $this->fileLength)
            $this->readBetweenTags();
    }

    private function readEndTag()
    {
        #check tag
        for (; $this->loopPosition < $this->fileLength; ++$this->loopPosition) {
            switch ($this->file[$this->loopPosition]) {
                case "\"":
                    if ($this->quotationMark == null) {
                        $this->quotationMark = "\"";
                    } elseif ($this->quotationMark == "\"") {
                        $this->quotationMark = null;
                    }
                    break;
                case '\'':
                    if ($this->quotationMark == null) {
                        $this->quotationMark = "'";
                    } elseif ($this->quotationMark == "'") {
                        $this->quotationMark = null;
                    }
                    break;
                case '>':
                    #character appears not in a string:
                    if ($this->quotationMark == null) {
                        $this->last = $this->loopPosition;
                        $this->inTag = false;
                    }
                    break;
                default:
                    break;
            }
            #discoverd end:
            if ($this->last != null)
                break;
        }

        #make sth according to tag data
        if ($this->last != null) {
            #get tag data:
            $tag_data = substr($this->file, $this->first + 1, ($this->last - $this->first));
            $tag_data = preg_replace("/[> ]/", "", $tag_data);

            if (strlen($tag_data) > 1) {
                $tag_data = substr($tag_data, 1);

                if ($tag_data == end($this->referenceStack)->name) {
                    $text_elem = $this->getText();
                    if ($text_elem !== null) {
                        array_push(end($this->referenceStack)->cont, $text_elem);
                    }
                    $this->textStart = $this->loopPosition + 1;
                    array_pop($this->referenceStack);
                }
                #else
                #echo "tag:'".$tag."' != '".end($this->referenceStack)->name."'";
            }

            $this->spaceCharacter = null;
            $this->tagIncludesEnd = null;
            $this->first = null;
            $this->last = null;
        }
        #increment to next position
        ++$this->loopPosition;
        if ($this->loopPosition < $this->fileLength)
            $this->readBetweenTags();
    }

    private function readBetweenTags()
    {
        #$i = 0;
        #search for next tag:
        for (; $this->loopPosition < $this->fileLength; ++$this->loopPosition) {
            #++$i;
            switch ($this->file[$this->loopPosition]) {
                case "\"":
                    if ($this->quotationMark == null) {
                        $this->quotationMark = "\"";
                    } elseif ($this->quotationMark == "\"") {
                        $this->quotationMark = null;
                    }
                    break;
                #Does not work with Texts like this: Worum geht's, as there will be no next comma
                /*case '\'':
                    if ($this->quotationMark == null) {
                        $this->quotationMark = "'";
                    } elseif ($this->quotationMark == "'") {
                        $this->quotationMark = null;
                    }
                    break;*/
                case '<':
                    #character appears not in a string:
                    if ($this->quotationMark == null) {
                        $this->first = $this->loopPosition;
                        $this->inTag = true;
                        $this->textEnd = $this->loopPosition;
                        break;
                    }
                    break;
                default:
                    break;
            }
            #discoverd start of tag:
            if ($this->first != null)
                break;
        }
        #echo "i: " . $i . "," . "s/e: " . $this->textStart . ", " . $this->textEnd . ";";

        ++$this->loopPosition;
        if ($this->loopPosition < $this->fileLength && $this->file[$this->loopPosition] != "/") {
            $this->readStartTag();
        } else {
            $this->readEndTag();
        }
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Extracts Attribute-Array from the provided string
     *
     * @param string $attr_seq sequence containing attributes
     * @return array containing attributes
     */
    private function readAttributes($attr_seq)
    {
        $attributes = [];

        #extract first attribute name:
        $equal_sign = strpos($attr_seq, "=");
        while ($equal_sign) {
            $attr_name = substr($attr_seq, 0, $equal_sign);
            $attr_name = preg_replace("/ /", "", $attr_name);

            #get attribute information
            $attr_seq = substr($attr_seq, $equal_sign + 1);

            if ($attr_seq[0] == "\"" || $attr_seq[0] = '\'') {
                $quot_close = strpos(substr($attr_seq, 1), "" . $attr_seq[0]);

                if ($quot_close) {
                    $attr_values = substr($attr_seq, 1, $quot_close);
                    $attr_seq = substr($attr_seq, $quot_close + 2);
                } else {
                    $attr_values = substr($attr_seq, 1);
                    $attr_seq = "";
                }

                $attr_values = $attr_values . " ";
                $attr_value_arr = [];

                while ($space_pos = strpos($attr_values, " ")) {
                    array_push($attr_value_arr, substr($attr_values, 0, $space_pos));
                    $attr_values = substr($attr_values, $space_pos + 1);
                }

                $attributes[$attr_name] = $attr_value_arr;
            } else {
                error_log("attribute had no quotation marks for its value!");
            }
            $equal_sign = strpos($attr_seq, "=");
        }

        return $attributes;
    }

    /**
     * Returns Text between start and end text pointers
     *
     * @return string if string is valid and not empty null if a problem occurred
     */
    private function getText()
    {
        if ($this->textEnd - $this->textStart > 1) {
            $text_elem = substr($this->file, $this->textStart, $this->textEnd - $this->textStart);
            $not_empty = preg_match("/([^ ])/", $text_elem);
            if ($not_empty === 1) {
                #remove spans
                $text_elem = str_replace("</span>", "", $text_elem);
                $text_elem = str_replace("<span class=\"results-highlight\">", "", $text_elem);
                return $text_elem;
            } else {
                return null;
            }
        }
        return null;
    }

    /**
     * contains an array of tags, who should stay in the text
     *
     * @param string $value name of <> element
     * @return bool true if element must not be resolved, false else
     */
    private function isUnresolvedTag($value)
    {
        $unresolved_tags = ["br", "hr", "b", "img", "span"];
        foreach ($unresolved_tags as $unresolved_tag) {
            if ($value == $unresolved_tag)
                return true;
        }
        return false;
    }
}