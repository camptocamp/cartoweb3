      <!-- outline -->

      <complexType name="OutlineRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="shapes" type="types:ArrayOfShape"/>
          <element name="maskMode" type="xsd:boolean"/>
          <element name="labelMode" type="xsd:boolean"/>
          <element name="wholeDataLayer" type="xsd:string"/>

          <element name="swissimageMode" type="types:SwissimageModeType"/>
        </all>
      </complexType>

      <complexType name="OutlineResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="area" type="xsd:double"/>
          <element name="isOutside" type="xsd:boolean"/>
          
          <element name="swissimageStatus" type="types:SwissimageStatusType"/>
          <element name="swissimageInternalStatus" type="xsd:integer"/>
          <element name="swissimageLevel2_50Area" type="xsd:double"/>
        </all>
      </complexType>

      <simpleType name="SwissimageModeType">
        <restriction base="xsd:string">
	  		<enumeration value="MODE_LEVEL1"/>
	  		<enumeration value="MODE_LEVEL2_25"/>
	  		<enumeration value="MODE_LEVEL2_50_200"/>
        </restriction>
      </simpleType>

      <simpleType name="SwissimageStatusType">
        <restriction base="xsd:string">
	  		<enumeration value="STATUS_VALID"/>
			<enumeration value="STATUS_MIXING_LEVELS"/>
			<enumeration value="STATUS_OUTSIDE_LEVEL1"/>
			<enumeration value="STATUS_OUTSIDE_LEVEL2"/>
			<enumeration value="STATUS_OUTSIDE_LEVEL2_25"/>
        </restriction>
      </simpleType>
      