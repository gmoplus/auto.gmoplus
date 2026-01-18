<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: MYPDF.CLASS.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

/**
 * MY PDF class
 * Extend the TCPDF class to create custom Header
 * @since 2.3.0
 */
class MYPDF extends TCPDF
{
    /**
     * @var string
     */
    private $customLogo = '';

    /**
     * @return string
     */
    public function getCustomLogo()
    {
        return $this->customLogo;
    }

    /**
     * @param string $customLogo
     */
    public function setCustomLogo($customLogo)
    {
        $this->customLogo = $customLogo;
    }

    public function Header()
    {
        if ($this->header_xobjid === false) {
            // start a new XObject Template
            $this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
            $headerfont = $this->getHeaderFont();
            $headerdata = $this->getHeaderData();
            $this->y = $this->header_margin;
            if ($this->rtl) {
                $this->x = $this->w - $this->original_rMargin;
            } else {
                $this->x = $this->original_lMargin;
            }

            $this->Image($this->customLogo, 10, 10, 55, '', '', '', 'T');
            $imgy = $this->getImageRBY();

            $cell_height = $this->getCellHeight($headerfont[2] / $this->k);
            // set starting margin for text data cell
            if ($this->getRTL()) {
                $header_x = $this->original_rMargin + ($headerdata['logo_width'] * 1.1);
            } else {
                $header_x = $this->original_lMargin + ($headerdata['logo_width'] * 1.1);
            }
            $cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($headerdata['logo_width'] * 1.1);
            $this->SetTextColorArray($this->header_text_color);
            // header title
            $this->SetFont($headerfont[0], 'B', $headerfont[2] + 1);
            $this->SetX($header_x);
            $this->Cell($cw, $cell_height, $headerdata['title'], 0, 1, '', 0, '', 0);
            // header string
            $this->SetFont($headerfont[0], $headerfont[1], $headerfont[2]);
            $this->SetX($header_x);
            $this->MultiCell(
                $cw,
                $cell_height,
                $headerdata['string'],
                0,
                '',
                0,
                1,
                '',
                '',
                true,
                0,
                false,
                true,
                0,
                'T',
                false
            );
            // print an ending header line
            $this->SetLineStyle(
                array(
                    'width' => 0.85 / $this->k,
                    'cap'   => 'butt',
                    'join'  => 'miter',
                    'dash'  => 0,
                    'color' => $headerdata['line_color']
                )
            );
            $this->SetY((2.835 / $this->k) + max($imgy, $this->y));
            if ($this->rtl) {
                $this->SetX($this->original_rMargin);
            } else {
                $this->SetX($this->original_lMargin);
            }
            $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
            $this->endTemplate();
        }
        // print header template
        $x = 0;
        $dx = 0;
        if (!$this->header_xobj_autoreset AND $this->booklet AND (($this->page % 2) == 0)) {
            // adjust margins for booklet mode
            $dx = ($this->original_lMargin - $this->original_rMargin);
        }
        if ($this->rtl) {
            $x = $this->w + $dx;
        } else {
            $x = 0 + $dx;
        }
        $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
        if ($this->header_xobj_autoreset) {
            // reset header xobject template at each page
            $this->header_xobjid = false;
        }
    }
}
