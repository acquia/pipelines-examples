<?php

	require_once( '../vendor/autoload.php' );
	$html1 = "<p><i>This is</i> some sample text to <strong>demonstrate</strong> the capability of the <strong>HTML diff tool</strong>.</p>
                                <p>It is based on the <b>Ruby</b> implementation found <a href='http://github.com/myobie/htmldiff'>here</a>. Note how the link has no tooltip</p>
                                <table cellpadding='0' cellspacing='0'>
                                <tr><td>Some sample text</td><td>Some sample value</td></tr>
                                <tr><td>Data 1 (this row will be removed)</td><td>Data 2</td></tr>
                                </table>
                                Here is a number 2 32
                                <h2>Heading number one</h2>
                                <p>This paragraph will be deleted.</p>
                                <p>Another paragraph variant 1.</p>
                                <p>The last paragraph.</p>
                                <ul>
                                  <li>Number 1</li>
                                  <li>Number 2</li>
                                  <li>Number 3</li>
                                </ul>
                                A table.
                                <table border='1'>
                                  <tr>
                                    <td>d1</td><td>d2</td>
                                  </tr><tr>
                                    <td>d3</td><td>d4</td>
                                  </tr><tr>
                                    <td>d5</td><td>d6</td>
                                  </tr>
                                </table>
                                Another table.
                                <table border='1'>
                                  <tr>
                                    <td>d1</td><td>d2</td>
                                  </tr><tr>
                                    <td>d3</td><td>d4</td>
                                  </tr><tr>
                                    <td>d5</td><td>d6</td>
                                  </tr>
                                </table>
                                ";
	$html2 = "<p>This is some sample <strong>text to</strong> demonstrate the awesome capabilities of the <strong>HTML <u>diff</u> tool</strong>.</p><br/><br/>Extra spacing here that was not here before.
                                <p>It is <i>based</i> on the Ruby implementation found <a title='Cool tooltip' href='http://github.com/myobie/htmldiff'>here</a>. Note how the link has a tooltip now and the HTML diff algorithm has preserved formatting.</p>
                                <table cellpadding='0' cellspacing='0'>
                                <tr><td>Some sample <strong>bold text</strong></td><td>Some sample value</td></tr>
                                </table>
                                Here is a number 2 <sup>32</sup>
                                <h2>Heading Number One</h2>
                                <p>Another paragraph variant 2.</p>
                                <p>The last paragraph.</p>
                                <ul>
                                  <li>Number 1</li>
                                  <li>Number 3</li>
                                </ul>
                                A table.
                                <table border='1'>
                                  <tr>
                                    <td>d1</td><td>d2</td><td>d3</td>
                                  </tr><tr>
                                    <td>d5</td><td>d6</td><td>d4</td>
                                  </tr>
                                </table>
                                Another table.
                                <table border='1'>
                                  <tr>
                                    <td>d1</td><td>d2</td>
                                  </tr><tr>
                                    <td>d5</td><td>d6</td>
                                  </tr>
                                </table>
                                ";
	$diff = new HtmlDiffAdvanced();
	$diff->setOldHtml($html1);
	$diff->setNewHtml($html2);
	echo "<h2>Old html</h2>";
	echo $diff->getOldHtml();
	echo "<hr><h2>New html</h2>";
	echo $diff->getNewHtml();
	echo "<hr><h2>Compared html</h2>";
	echo $diff->getDifference();
