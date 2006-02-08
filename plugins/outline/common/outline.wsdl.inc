      <!-- outline -->

      <complexType name="StyledShape">
        <all>
          <element name="shapeStyle" type="types:StyleOverlay"/>
          <element name="labelStyle" type="types:LabelOverlay"/>
          <element name="shape" type="types:Shape"/>
          <element name="label" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ArrayOfStyledShape">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:StyledShape[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="OutlineRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="shapes" type="types:ArrayOfStyledShape"/>
          <element name="maskMode" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="OutlineResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="area" type="xsd:double"/>
        </all>
      </complexType>

      <complexType name="OutlineInit">
        <all>
          <element name="point" type="types:ArrayOfString"/>
          <element name="pointLabels" type="types:ArrayOfString"/>
          <element name="line" type="types:ArrayOfString"/>
          <element name="polygon" type="types:ArrayOfString"/>
          <element name="pathToSymbols" type="xsd:string"/>
          <element name="symbolType" type="xsd:string"/>
          <element name="outlineDefaultValues" type="types:ArrayOfDefaultValues"/>
        </all>
      </complexType>

      <complexType name="ArrayOfDefaultValues">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:DefaultValues[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="DefaultValues">
        <all>
          <element name="type" type="xsd:string"/>
          <element name="shapeStyle" type="types:StyleOverlay"/>
        </all>
      </complexType>

