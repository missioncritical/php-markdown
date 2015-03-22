<?php

class Markdown_ParserExtraTest extends CriticalI_TestCase {
  
  /**
   * @dataProvider phpMarkdownTests
   */
  public function testMarkdown($srcFile, $expectedFile) {
    // skip the emphasis test for now; unsure if 'tricky' cases are supposed to pass
    if ("PHPMarkdown.mdtest" == basename(dirname($srcFile)) &&
        "Emphasis.text" === basename($srcFile)) 
      return $this->markTestSkipped("Skipping known failures in emphasis test");
      
    $src = file_get_contents($srcFile);
    $expected = file_get_contents($expectedFile);
    
    $parser = new Markdown_ParserExtra();
    $parser->fn_link_class = '';
    $parser->fn_backlink_class = '';
    
    $result =  $parser->transform($src);
    
    $this->compareResult($expected, $result);
  }
  
  /**
   * Data provider for testMarkdown
   */
  public function phpMarkdownTests() {
    return array_merge(
      $this->getMarkdownTests(dirname(__FILE__) . '/../data/Markdown.mdtest'),
      $this->getMarkdownTests(dirname(__FILE__) . '/../data/PHPMarkdown.mdtest'),
      $this->getMarkdownTests(dirname(__FILE__) . '/../data/PHPMarkdownExtra.mdtest')
    );
  }
  
  
  /**
   * Assemble the list of test arguments from a directory of test data
   */
  protected function getMarkdownTests($dir) {
    $tests = array();
    
    $dh = opendir($dir);

    while (($entry = readdir($dh)) !== false) {
      if (is_file("$dir/$entry") && pathinfo($entry, PATHINFO_EXTENSION) == 'text') {
        
        $result = basename($entry, '.text') . '.xhtml';
        $result = file_exists("$dir/$result") ? $result : (basename($entry, '.text') . '.html');
        
        $tests[] = array("$dir/$entry", "$dir/$result", basename($entry, '.text'));
      }
    }
    
    closedir($dh);
    
    return $tests;
  }
  
  /**
   * Compare two html results
   */
  protected function compareResult($expected, $actual) {
    $this->assertEquals($this->normalize($expected), $this->normalize($actual));
  }
  
  /**
   * Normalize HTML output
   */
  protected function normalize($html) {
    $doc = DOMDocument::loadHTML(
      '<!DOCTYPE html>' .
			'<html>' .
			"<body>$html</body>" .
			'</html>');
		
		$this->normalizeElementContent($doc->documentElement);
		
		return $doc->saveHTML();
  }
  

  /**
   * Normalize content of HTML DOM $element. The $whitespace_preserve 
   * argument indicates that whitespace is significant and shouldn't be 
   * normalized; it should be used for the content of certain elements like
   * <pre> or <script>.
   */
  protected function normalizeElementContent($element, $whitespace_preserve = false) {
  	$node_list = $element->childNodes;
  	switch (strtolower($element->nodeName)) {
  		case 'body':
  		case 'div':
  		case 'blockquote':
  		case 'ul':
  		case 'ol':
  		case 'dl':
  		case 'h1':
  		case 'h2':
  		case 'h3':
  		case 'h4':
  		case 'h5':
  		case 'h6':
  			$whitespace = "\n\n";
  			break;
			
  		case 'table':
  			$whitespace = "\n";
  			break;
		
  		case 'pre':
  		case 'script':
  		case 'style':
  		case 'title':
  			$whitespace_preserve = true;
  			$whitespace = "";
  			break;
		
  		default:
  			$whitespace = "";
  			break;
  	}
  	foreach ($node_list as $node) {
  		switch ($node->nodeType) {
  			case XML_ELEMENT_NODE:
  				$this->normalizeElementContent($node, $whitespace_preserve);
  				$this->normalizeElementAttributes($node);
				
  				switch (strtolower($node->nodeName)) {
  					case 'p':
  					case 'div':
  					case 'hr':
  					case 'blockquote':
  					case 'ul':
  					case 'ol':
  					case 'dl':
  					case 'li':
  					case 'address':
  					case 'table':
  					case 'dd':
  					case 'pre':
  					case 'h1':
  					case 'h2':
  					case 'h3':
  					case 'h4':
  					case 'h5':
  					case 'h6':
  						$whitespace = "\n\n";
  						break;
					
  					case 'tr':
  					case 'td':
  					case 'dt':
  						$whitespace = "\n";
  						break;
					
  					default:
  						$whitespace = "";
  						break;
  				}
				
  				if (($whitespace == "\n\n" || $whitespace == "\n") &&
  					$node->nextSibling && 
  					$node->nextSibling->nodeType != XML_TEXT_NODE)
  				{
  					$element->insertBefore(new DOMText($whitespace), $node->nextSibling);
  				}
  				break;
				
  			case XML_TEXT_NODE:
  				if (!$whitespace_preserve) {
  					if (trim($node->data) == "") {
  						$node->data = $whitespace;
  					} else {
  						$node->data = preg_replace('{\s+}', ' ', $node->data);
  					}
  				}
  				break;
  		}
  	}
  	if (!$whitespace_preserve && 
  		($whitespace == "\n\n" || $whitespace == "\n"))
  	{
  		if ($element->firstChild) {
  			if ($element->firstChild->nodeType == XML_TEXT_NODE) {
  				$element->firstChild->data = 
  					preg_replace('{^\s+}', "\n", $element->firstChild->data);
  			} else {
  				$element->insertBefore(new DOMText("\n"), $element->firstChild);
  			}
  		}
  		if ($element->lastChild) {
  			if ($element->lastChild->nodeType == XML_TEXT_NODE) {
  				$element->lastChild->data = 
  					preg_replace('{\s+$}', "\n", $element->lastChild->data);
  			} else {
  				$element->insertBefore(new DOMText("\n"), null);
  			}
  		}
  	}
  }


  /**
   * Sort attributes by name.
   */
  protected function normalizeElementAttributes($element) {
  	// Gather the list of attributes as an array.
  	$attr_list = array();
  	foreach ($element->attributes as $attr_node) {
  		$attr_list[$attr_node->name] = $attr_node;
  	}
	
  	// Sort attribute list by name.
  	ksort($attr_list);

  	// Remove then put back each attribute following sort order.
  	foreach ($attr_list as $attr_node) {
  		$element->removeAttributeNode($attr_node);
  		$element->setAttributeNode($attr_node);
  	}
  }

}
