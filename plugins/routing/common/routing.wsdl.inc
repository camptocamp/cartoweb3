      <!-- routing -->

      <complexType name="Parameter">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="id" type="xsd:string"/>
          <element name="value" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ArrayOfParameter">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:Parameter[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="Attribute">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="id" type="xsd:string"/>
          <element name="value" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ArrayOfAttribute">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:Attribute[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="Step">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="attributes" type="types:ArrayOfAttribute"/>
        </all>
      </complexType>

      <complexType name="ArrayOfStep">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:Step[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="RoutingRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="graph" type="xsd:string"/>
          <element name="stops" type="types:ArrayOfString"/>
          <element name="parameters" type="types:ArrayOfParameter"/>
        </all>
      </complexType>

      <complexType name="RoutingResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="graph" type="xsd:string"/>
          <element name="steps" type="types:ArrayOfStep"/>
          <element name="attributes" type="types:ArrayOfAttribute"/>
        </all>
      </complexType>
 
