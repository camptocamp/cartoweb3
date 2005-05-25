      <!-- routing -->

      <complexType name="Step">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="attributes" type="types:ArrayOfString"/>
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
          <element name="stops" type="types:ArrayOfString"/>
          <element name="parameters" type="types:ArrayOfString"/>
          <element name="path" type="types:ArrayOfStyledShape"/>
        </all>
      </complexType>

      <complexType name="RoutingResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="path" type="types:ArrayOfStyledShape"/>
          <element name="steps" type="types:ArrayOfStep"/>
          <element name="attributes" type="types:ArrayOfString"/>
        </all>
      </complexType>
