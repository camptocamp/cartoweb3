      <!-- outline -->

      <complexType name="OutlineRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="shapes" type="types:ArrayOfShape"/>
          <element name="maskMode" type="xsd:boolean"/>
          <element name="labelMode" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="OutlineResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="area" type="xsd:double"/>
        </all>
      </complexType>
