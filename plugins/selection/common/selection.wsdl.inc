      <!-- selection -->

      <complexType name="SelectionRequest">
        <all>
          <element name="policy" type="types:SelectionPolicy"/>
          <element name="bbox" type="types:Bbox"/>
        </all>
      </complexType>

      <complexType name="SelectionResult">
        <all>
          <element name="selectedIds" type="types:ArrayOfLayerId"/>
        </all>      
      </complexType>

      <simpleType name="SelectionPolicy">
        <restriction base="xsd:string">
          <enumeration value="POLICY_XOR"/>
          <enumeration value="POLICY_UNION"/>
          <enumeration value="POLICY_INTERSECTION"/>
        </restriction>
      </simpleType>

      <complexType name="SelectionInit">
        <all>
        </all>      
      </complexType>
      