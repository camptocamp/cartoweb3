      <!-- projectLocation -->

      <complexType name="ProjectLocationRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="locationRequest" type="types:LocationRequest"/>
          <element name="projectRequest" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ProjectLocationResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="locationResult" type="types:LocationResult"/>
          <element name="projectResult" type="xsd:string"/>
        </all>
      </complexType>
