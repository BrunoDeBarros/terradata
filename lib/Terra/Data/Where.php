<?php

class Terra_Data_Where {

    protected $WhereClause = array();
    protected $Table = '';

    function __construct($Table = null) {
        if ($Table != null) {
            $this->Table = "`".$Table."`.";
        }
    }

    function importFromArray($Array) {
        foreach ($Array as $Field => $Value) {
            $this->_and($Field, $Value);
        }
    }

    function _and($Field, $Value = '', $QuoteAndEscape = true) {
        $this->addClause($Field, 'AND', $Value, $QuoteAndEscape);
        return $this;
    }

    protected function addClause($Field, $AndOr = 'AND', $Value = '', $QuoteAndEscape = true) {
        if (empty($Field)) {
            return false;
        }

        if ($Field instanceof Terra_Data_Where) {
            $this->WhereClause[] = array(
                    'AndOr' => $AndOr,
                    'String' => (string) $Field
            );
        } else {
            $Field = explode(' ', $Field, 2);
            if (isset($Field[1])) {
                $Condition = $Field[1];
            } else {
                $Condition = '=';
            }
            $Field = $Field[0];

            if (count(explode('.', $Field)) == 1) {
                $Field = $this->Table.$Field;
            }

            if (is_array($Value)) {
                # If it's an array, it means it can be any of those values, so... it's time to add each of them as an OR.
                $mega_or = new Terra_Data_Where($this->Table);
                
                foreach ($Value as $PossibleValue) {
                    $mega_or->_or($Field, $PossibleValue);
                }

                $this->WhereClause[] = array(
                        'AndOr' => $AndOr,
                        'String' => (string) $mega_or
                );
            } else {
                if ($QuoteAndEscape) {
                    $Value = "'".Terra_Data::escape($Value)."'";
                }

                $string = "($Field $Condition $Value)";

                $this->WhereClause[] = array(
                        'AndOr' => $AndOr,
                        'String' => $string
                );
            }
        }
    }

    function _or($Field, $Value = '', $QuoteAndEscape = true) {
        $this->addClause($Field, 'OR', $Value, $QuoteAndEscape);
        return $this;
    }

    function  __toString() {
        $i = 0;
        $sql = '(';
        foreach($this->WhereClause as $Clause) {

            if ($i != 0) {
                $andor = $Clause['AndOr'].' ';
            } else {
                $andor = '';
            }

            $sql .= "$andor{$Clause['String']} ";

            $i = 1;
        }
        return $sql.')';
    }
}