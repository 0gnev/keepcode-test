<?php

namespace App\Faker;

use Faker\Provider\Base;

class ToolProvider extends Base
{
    protected static array $tools = [
        ['name' => 'Bosch GSB 13 RE Impact Drill', 'category' => 'Drills', 'company' => 'Bosch'],
        ['name' => 'DeWalt DCD996 Cordless Hammer Drill', 'category' => 'Drills', 'company' => 'DeWalt'],
        ['name' => 'Makita HR2475 SDS-Plus Rotary Hammer', 'category' => 'Hammers', 'company' => 'Makita'],
        ['name' => 'Milwaukee 2715-20 M18 Fuel Rotary Hammer', 'category' => 'Hammers', 'company' => 'Milwaukee'],
        ['name' => 'Stanley STHT51304 16-Ounce Rip Claw Hammer', 'category' => 'Hammers', 'company' => 'Stanley'],
        ['name' => 'Klein Tools 8-Inch Long Nose Pliers', 'category' => 'Pliers', 'company' => 'Klein Tools'],
        ['name' => 'Irwin VISE-GRIP Locking Pliers', 'category' => 'Pliers', 'company' => 'Irwin'],
        ['name' => 'Craftsman CMHT65075 Screwdriver Set', 'category' => 'Screwdrivers', 'company' => 'Craftsman'],
        ['name' => 'Wiha 32092 Precision Screwdriver', 'category' => 'Screwdrivers', 'company' => 'Wiha'],
        ['name' => 'Snap-On SOEX710 Flank Drive Wrench Set', 'category' => 'Wrenches', 'company' => 'Snap-On'],
        ['name' => 'GearWrench 120XP Flex Head Ratcheting Wrench Set', 'category' => 'Wrenches', 'company' => 'GearWrench'],
        ['name' => 'Ridgid 31100 Model 818 Pipe Wrench', 'category' => 'Wrenches', 'company' => 'Ridgid'],
        ['name' => 'Hitachi C10FCG 10-Inch Miter Saw', 'category' => 'Saws', 'company' => 'Hitachi'],
        ['name' => 'Ryobi P507 One+ Circular Saw', 'category' => 'Saws', 'company' => 'Ryobi'],
        ['name' => 'Festool TS 55 REQ Plunge Cut Track Saw', 'category' => 'Saws', 'company' => 'Festool'],
        ['name' => 'Dremel 8220 Cordless Rotary Tool', 'category' => 'Rotary Tools', 'company' => 'Dremel'],
        ['name' => 'Black+Decker BDERO100 Random Orbit Sander', 'category' => 'Sanders', 'company' => 'Black+Decker'],
        ['name' => 'Makita BO5030K Random Orbit Sander', 'category' => 'Sanders', 'company' => 'Makita'],
        ['name' => 'DeWalt DCW210B Cordless Orbital Sander', 'category' => 'Sanders', 'company' => 'DeWalt'],
    ];

    public function toolName()
    {
        return $this->tool()['name'];
    }

    public function tool()
    {
        return static::randomElement(static::$tools);
    }

    public function toolCategory()
    {
        return $this->tool()['category'];
    }

    public function toolCompany()
    {
        return $this->tool()['company'];
    }
}
